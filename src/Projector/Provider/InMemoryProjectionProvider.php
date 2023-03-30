<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Provider;

use Illuminate\Support\Collection;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use function in_array;
use function array_keys;
use function array_key_exists;

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

    public function createProjection(string $name, string $status): bool
    {
        if ($this->projectionExists($name)) {
            return false;
        }

        $this->projections->put($name, InMemoryProjection::create($name, $status));

        return true;
    }

    public function updateProjection(string $name, array $data): bool
    {
        $this->assertFillable($data, $name);

        $projection = $this->retrieve($name);

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

    public function deleteProjection(string $name): bool
    {
        if (! $this->projections->has($name)) {
            return false;
        }

        $this->projections->forget($name);

        return true;
    }

    public function acquireLock(string $name, string $status, string $lockedUntil, string $datetime): bool
    {
        if (! $projection = $this->retrieve($name)) {
            return false;
        }

        if ($this->shouldUpdateLock($projection, $datetime)) {
            $projection->setStatus($status);

            $projection->setLockedUntil($lockedUntil);

            return true;
        }

        return false;
    }

    public function retrieve(string $name): ?ProjectionModel
    {
        return $this->projections->get($name);
    }

    public function filterByNames(string ...$projectionNames): array
    {
        $byStreamNames = fn (InMemoryProjection $projection): bool => in_array($projection->name(), $projectionNames);

        return $this->projections->filter($byStreamNames)->keys()->toArray();
    }

    public function projectionExists(string $name): bool
    {
        return $this->projections->has($name);
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
                throw new InvalidArgumentException("Invalid projection field $key for projection $name");
            }
        }
    }
}
