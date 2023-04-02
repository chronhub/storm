<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Throwable;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Exceptions\InMemoryProjectionFailed;
use Chronhub\Storm\Contracts\Projector\ProjectionManagerInterface;

final readonly class InMemoryProjectionManager implements ProjectionManagerInterface
{
    public function __construct(private ProjectionManagerInterface $manager)
    {
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function create(): bool
    {
        try {
            $created = $this->manager->create();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $created) {
            throw InMemoryProjectionFailed::failedOnCreate($this->projectionName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function stop(): bool
    {
        try {
            $stopped = $this->manager->stop();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $stopped) {
            throw InMemoryProjectionFailed::failedOnStop($this->projectionName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function startAgain(): bool
    {
        try {
            $restarted = $this->manager->startAgain();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $restarted) {
            throw InMemoryProjectionFailed::failedOnStartAgain($this->projectionName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function persist(): bool
    {
        try {
            $persisted = $this->manager->persist();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $persisted) {
            throw InMemoryProjectionFailed::failedOnPersist($this->projectionName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function reset(): bool
    {
        try {
            $reset = $this->manager->reset();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $reset) {
            throw InMemoryProjectionFailed::failedOnReset($this->projectionName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function delete(bool $withEmittedEvents): bool
    {
        try {
            $deleted = $this->manager->delete($withEmittedEvents);
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $deleted) {
            throw InMemoryProjectionFailed::failedOnDelete($this->projectionName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function acquireLock(): bool
    {
        try {
            $locked = $this->manager->acquireLock();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $locked) {
            throw InMemoryProjectionFailed::failedOnAcquireLock($this->projectionName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function updateLock(): bool
    {
        try {
            $updated = $this->manager->updateLock();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $updated) {
            throw InMemoryProjectionFailed::failedOnUpdateLock($this->projectionName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function releaseLock(): bool
    {
        try {
            $released = $this->manager->releaseLock();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $released) {
            throw InMemoryProjectionFailed::failedOnReleaseLock($this->projectionName());
        }

        return true;
    }

    public function loadState(): bool
    {
        return $this->manager->loadState();
    }

    public function loadStatus(): ProjectionStatus
    {
        return $this->manager->loadStatus();
    }

    public function exists(): bool
    {
        return $this->manager->exists();
    }

    public function projectionName(): string
    {
        return $this->manager->projectionName();
    }
}
