<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ProjectionData;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Projector\Exceptions\InMemoryProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyExists;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use function in_array;

final readonly class InMemoryProjectionProvider implements ProjectionProvider
{
    /**
     * @var Collection<non-empty-string,InMemoryProjection>
     */
    private Collection $projections;

    public function __construct(private SystemClock $clock)
    {
        $this->projections = new Collection();
    }

    public function createProjection(string $projectionName, ProjectionData $data): void
    {
        if ($this->exists($projectionName)) {
            throw ProjectionAlreadyExists::withName($projectionName);
        }

        $this->projections->put($projectionName, InMemoryProjection::create($projectionName, $data->toArray()['status']));
    }

    public function acquireLock(string $projectionName, ProjectionData $data): void
    {
        $projection = $this->tryRetrieve($projectionName);

        if (! $this->canAcquireLock($projection)) {
            throw InMemoryProjectionFailed::failedOnAcquireLock($projectionName);
        }

        $this->applyChanges($projection, $data->toArray());
    }

    public function updateProjection(string $projectionName, ProjectionData $data): void
    {
        $projection = $this->tryRetrieve($projectionName);

        $this->applyChanges($projection, $data->toArray());
    }

    public function deleteProjection(string $projectionName): void
    {
        if (! $this->exists($projectionName)) {
            throw ProjectionNotFound::withName($projectionName);
        }

        $this->projections->forget($projectionName);
    }

    public function retrieve(string $projectionName): ?ProjectionModel
    {
        return $this->projections->get($projectionName);
    }

    public function filterByNames(string ...$projectionNames): array
    {
        $byStreamNames = static fn (InMemoryProjection $projection): bool => in_array($projection->name(), $projectionNames, true);

        return $this->projections->filter($byStreamNames)->keys()->toArray();
    }

    public function exists(string $projectionName): bool
    {
        return $this->projections->has($projectionName);
    }

    private function canAcquireLock(ProjectionModel $projection): bool
    {
        if ($projection->lockedUntil() === null) {
            return true;
        }

        return $this->clock->isGreaterThanNow($projection->lockedUntil());
    }

    private function tryRetrieve(string $projectionName): InMemoryProjection
    {
        $projection = $this->retrieve($projectionName);

        if (! $projection instanceof InMemoryProjection) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $projection;
    }

    private function applyChanges(InMemoryProjection $projection, array $data): void
    {
        foreach ($data as $key => $value) {
            $method = 'set'.Str::studly($key);

            $projection->$method($value);
        }
    }
}
