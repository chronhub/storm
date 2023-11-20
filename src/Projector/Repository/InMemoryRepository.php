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
        return $this->tryOperation(
            fn (): bool => $this->repository->create($status),
            'Failed on create'
        );
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function stop(ProjectionDetail $projectionDetail): bool
    {
        return $this->tryOperation(
            fn (): bool => $this->repository->stop($projectionDetail),
            'Failed on stop'
        );
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function startAgain(): bool
    {
        return $this->tryOperation(
            fn (): bool => $this->repository->startAgain(),
            'Failed on start again'
        );
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function persist(ProjectionDetail $projectionDetail): bool
    {
        return $this->tryOperation(
            fn (): bool => $this->repository->persist($projectionDetail),
            'Failed on persist'
        );
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function reset(ProjectionDetail $projectionDetail, ProjectionStatus $currentStatus): bool
    {
        return $this->tryOperation(
            fn (): bool => $this->repository->reset($projectionDetail, $currentStatus),
            'Failed on reset'
        );
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function delete(): bool
    {
        return $this->tryOperation(
            fn (): bool => $this->repository->delete(),
            'Failed on delete'
        );
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function acquireLock(): bool
    {
        return $this->tryOperation(
            fn (): bool => $this->repository->acquireLock(),
            'Failed on acquire lock'
        );
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function attemptUpdateStreamPositions(array $streamPositions): bool
    {
        return $this->tryOperation(
            fn (): bool => $this->repository->attemptUpdateStreamPositions($streamPositions),
            'Failed on update stream positions'
        );
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function releaseLock(): bool
    {
        return $this->tryOperation(
            fn (): bool => $this->repository->releaseLock(),
            'Failed on release lock'
        );
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

    private function tryOperation(callable $operation, string $failedMessage): bool
    {
        try {
            $result = $operation();
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::fromProjectionException($exception, $failedMessage);
        }

        if ($result === true) {
            return true;
        }

       throw InMemoryProjectionFailed::failedOnOperation($failedMessage.' for projection name '.$this->projectionName());
    }
}
