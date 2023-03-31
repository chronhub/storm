<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Throwable;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Contracts\Projector\ProjectionStore;
use Chronhub\Storm\Projector\Exceptions\InMemoryProjectionFailed;

final readonly class InMemoryProjectionStore implements ProjectionStore
{
    public function __construct(private ProjectionStore $provider)
    {
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function create(): bool
    {
        try {
            $created = $this->provider->create();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $created) {
            throw InMemoryProjectionFailed::failedOnCreate($this->currentStreamName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function stop(): bool
    {
        try {
            $stopped = $this->provider->stop();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $stopped) {
            throw InMemoryProjectionFailed::failedOnStop($this->currentStreamName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function startAgain(): bool
    {
        try {
            $restarted = $this->provider->startAgain();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $restarted) {
            throw InMemoryProjectionFailed::failedOnStartAgain($this->currentStreamName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function persist(): bool
    {
        try {
            $persisted = $this->provider->persist();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $persisted) {
            throw InMemoryProjectionFailed::failedOnPersist($this->currentStreamName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function reset(): bool
    {
        try {
            $reset = $this->provider->reset();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $reset) {
            throw InMemoryProjectionFailed::failedOnReset($this->currentStreamName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function delete(bool $withEmittedEvents): bool
    {
        try {
            $deleted = $this->provider->delete($withEmittedEvents);
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $deleted) {
            throw InMemoryProjectionFailed::failedOnDelete($this->currentStreamName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function acquireLock(): bool
    {
        try {
            $locked = $this->provider->acquireLock();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $locked) {
            throw InMemoryProjectionFailed::failedOnAcquireLock($this->currentStreamName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function updateLock(): bool
    {
        try {
            $updated = $this->provider->updateLock();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $updated) {
            throw InMemoryProjectionFailed::failedOnUpdateLock($this->currentStreamName());
        }

        return true;
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function releaseLock(): bool
    {
        try {
            $released = $this->provider->releaseLock();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $released) {
            throw InMemoryProjectionFailed::failedOnReleaseLock($this->currentStreamName());
        }

        return true;
    }

    public function loadState(): bool
    {
        return $this->provider->loadState();
    }

    public function loadStatus(): ProjectionStatus
    {
        return $this->provider->loadStatus();
    }

    public function exists(): bool
    {
        return $this->provider->exists();
    }

    public function currentStreamName(): string
    {
        return $this->provider->currentStreamName();
    }
}
