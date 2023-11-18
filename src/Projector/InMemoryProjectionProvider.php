<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Illuminate\Support\Collection;

use function in_array;

final readonly class InMemoryProjectionProvider implements ProjectionProvider
{
    /**
     * @var Collection<string, InMemoryProjection>
     */
    private Collection $projections;

    public function __construct(private SystemClock $clock)
    {
        $this->projections = new Collection();
    }

    public function createProjection(string $projectionName, string $status): bool
    {
        if ($this->exists($projectionName)) {
            throw new RuntimeException("Projection $projectionName already exists");
        }

        $this->projections->put($projectionName, InMemoryProjection::create($projectionName, $status));

        return true;
    }

    //checkMe acquireLock could be the last args of updateProjection
    public function acquireLock(string $projectionName, string $status, string $lockedUntil): bool
    {
        $projection = $this->retrieve($projectionName);

        if (! $projection instanceof InMemoryProjection) {
            throw ProjectionNotFound::withName($projectionName);
        }

        if ($this->shouldUpdateLock($projection)) {
            $projection->setStatus($status);

            $projection->setLockedUntil($lockedUntil);

            return true;
        }

        return false;
    }

    public function updateProjection(
        string $projectionName,
        string $status = null,
        string $state = null,
        string $positions = null,
        string $gaps = null,
        bool|string|null $lockedUntil = false
    ): bool {
        $projection = $this->retrieve($projectionName);

        if (! $projection instanceof InMemoryProjection) {
            throw ProjectionNotFound::withName($projectionName);
        }

        if ($projection->lockedUntil() === null) {
            throw new RuntimeException("Projection lock must be acquired before updating projection $projectionName");
        }

        if ($status !== null) {
            $projection->setStatus($status);
        }

        if ($state !== null) {
            $projection->setState($state);
        }

        if ($positions !== null) {
            $projection->setPosition($positions);
        }

        if ($gaps !== null) {
            $projection->setGaps($gaps);
        }

        if ($lockedUntil !== false) {
            $projection->setLockedUntil($lockedUntil);
        }

        return true;
    }

    public function deleteProjection(string $projectionName): bool
    {
        if (! $this->exists($projectionName)) {
            throw ProjectionNotFound::withName($projectionName);
        }

        $this->projections->forget($projectionName);

        return true;
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

    private function shouldUpdateLock(ProjectionModel $projection): bool
    {
        if ($projection->lockedUntil() === null) {
            return true;
        }

        return $this->clock->isGreaterThanNow($projection->lockedUntil());
    }
}
