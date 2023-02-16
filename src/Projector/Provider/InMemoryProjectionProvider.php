<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Provider;

use DateTimeZone;
use DateTimeImmutable;
use Illuminate\Support\Collection;
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

    public function __construct()
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
        foreach (array_keys($data) as $key) {
            if (! in_array($key, $this->fillable, true)) {
                throw new InvalidArgumentException("Invalid projection field $key for projection $name");
            }
        }

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
        $projection = $this->retrieve($name);

        if ($projection instanceof InMemoryProjection && $this->shouldUpdateLock($projection, $datetime)) {
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

    public function filterByNames(string ...$names): array
    {
        return $this->projections
            ->filter(static fn (InMemoryProjection $projection, string $name): bool => in_array($name, $names))
            ->keys()
            ->toArray();
    }

    public function projectionExists(string $name): bool
    {
        return $this->projections->has($name);
    }

    private function shouldUpdateLock(ProjectionModel $projection, string $now): bool
    {
        if ($projection->lockedUntil() === null) {
            return true;
        }

        $now = new DateTimeImmutable($now, new DateTimeZone('UTC'));

        return $now > new DateTimeImmutable($projection->lockedUntil(), new DateTimeZone('UTC'));
    }
}
