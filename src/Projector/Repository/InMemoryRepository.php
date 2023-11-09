<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Projector\Exceptions\InMemoryProjectionFailed;
use Chronhub\Storm\Projector\ProjectionStatus;
use Throwable;

final readonly class InMemoryRepository implements ProjectionRepositoryInterface
{
    public function __construct(private ProjectionRepositoryInterface $repository)
    {
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function create(ProjectionStatus $status): bool
    {
        try {
            $created = $this->repository->create($status);
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
    public function stop(ProjectionDetail $projectionDetail): bool
    {
        try {
            $stopped = $this->repository->stop($projectionDetail);
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
            $restarted = $this->repository->startAgain();
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
    public function persist(ProjectionDetail $projectionDetail): bool
    {
        try {
            $persisted = $this->repository->persist($projectionDetail);
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
    public function reset(ProjectionDetail $projectionDetail, ProjectionStatus $currentStatus): bool
    {
        try {
            $reset = $this->repository->reset($projectionDetail, $currentStatus);
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
    public function delete(): bool
    {
        try {
            $deleted = $this->repository->delete();
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
            $locked = $this->repository->acquireLock();
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
    public function updateLock(array $streamPositions): bool
    {
        try {
            $updated = $this->repository->updateLock($streamPositions);
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
            $released = $this->repository->releaseLock();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception);
        }

        if (! $released) {
            throw InMemoryProjectionFailed::failedOnReleaseLock($this->projectionName());
        }

        return true;
    }

    public function loadDetail(): ProjectionDetail
    {
        return $this->repository->loadDetail();
    }

    public function loadStatus(): ProjectionStatus
    {
        return $this->repository->loadStatus();
    }

    public function exists(): bool
    {
        return $this->repository->exists();
    }

    public function projectionName(): string
    {
        return $this->repository->projectionName();
    }
}
