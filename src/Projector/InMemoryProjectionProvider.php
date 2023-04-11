<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Illuminate\Support\Collection;
use function array_key_exists;
use function array_keys;
use function in_array;
use function sprintf;

final class InMemoryProjectionProvider implements ProjectionProvider
{
    /**
     * @var Collection<string, InMemoryProjection>
     */
    private readonly Collection $projections;

    private array $fillable = ['state', 'position', 'status', 'locked_until'];

    public function __construct(private readonly SystemClock $clock)
    {
        $this->projections = new Collection();
    }

    public function createProjection(string $projectionName, string $status): bool
    {
        if ($this->exists($projectionName)) {
            return false;
        }

        $this->projections->put($projectionName, InMemoryProjection::create($projectionName, $status));

        return true;
    }

    public function updateProjection(string $projectionName, array $data): bool
    {
        $this->assertFillable($data, $projectionName);

        $projection = $this->retrieve($projectionName);

        if ($projection instanceof InMemoryProjection) {
            if (isset($data['state'])) {
                $projection->setState($data['state']);
            }

            if (isset($data['position'])) {
                $projection->setPosition($data['position']);
            }

            if (isset($data['status'])) {
                $projection->setStatus($data['status']);
            }

            if (array_key_exists('locked_until', $data)) {
                $projection->setLockedUntil($data['locked_until']);
            }

            return true;
        }

        return false;
    }

    public function deleteProjection(string $projectionName): bool
    {
        if (! $this->projections->has($projectionName)) {
            return false;
        }

        $this->projections->forget($projectionName);

        return true;
    }

    public function acquireLock(string $projectionName, string $status, string $lockedUntil, string $datetime): bool
    {
        $projection = $this->retrieve($projectionName);

        if (! $projection instanceof InMemoryProjection) {
            return false;
        }

        if ($this->shouldUpdateLock($projection, $datetime)) {
            $projection->setStatus($status);

            $projection->setLockedUntil($lockedUntil);

            return true;
        }

        return false;
    }

    public function retrieve(string $projectionName): ?ProjectionModel
    {
        return $this->projections->get($projectionName);
    }

    public function filterByNames(string ...$projectionNames): array
    {
        $byStreamNames = static fn (InMemoryProjection $projection): bool => in_array($projection->name(), $projectionNames);

        return $this->projections->filter($byStreamNames)->keys()->toArray();
    }

    public function exists(string $projectionName): bool
    {
        return $this->projections->has($projectionName);
    }

    private function shouldUpdateLock(ProjectionModel $projection, string $currentTime): bool
    {
        if ($projection->lockedUntil() === null) {
            return true;
        }

        return $this->clock->isGreaterThan($currentTime, $projection->lockedUntil());
    }

    private function assertFillable(array $data, string $name): void
    {
        foreach (array_keys($data) as $key) {
            if (! in_array($key, $this->fillable, true)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid projection field %s for projection %s', $key, $name)
                );
            }
        }
    }
}
