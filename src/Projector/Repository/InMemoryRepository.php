<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\ProjectionStatus;
use DateTimeImmutable;

final readonly class InMemoryRepository implements ProjectionRepositoryInterface
{
    public function __construct(
        public ProjectionProvider $provider,
        public LockManager $lockManager,
        public JsonSerializer $serializer,
        public string $streamName
    ) {
    }

    public function create(ProjectionStatus $status): void
    {
        $this->provider->createProjection($this->streamName, $status->value);
    }

    public function start(): void
    {
        $this->provider->acquireLock(
            $this->streamName,
            ProjectionStatus::RUNNING->value,
            $this->lockManager->acquire(),
        );
    }

    public function stop(ProjectionDetail $projectionDetail): void
    {
        $this->persist($projectionDetail, ProjectionStatus::IDLE, null);
    }

    public function release(): void
    {
        $this->provider->updateProjection(
            $this->streamName,
            status: ProjectionStatus::IDLE->value,
            lockedUntil: null,
        );
    }

    public function startAgain(): void
    {
        $this->provider->updateProjection(
            $this->streamName,
            status: ProjectionStatus::RUNNING->value,
            lockedUntil: $this->lockManager->acquire(),
        );
    }

    public function persist(ProjectionDetail $projectionDetail, ProjectionStatus $currentStatus): void
    {
        $this->provider->updateProjection(
            $this->streamName,
            status: $currentStatus->value,
            state: $this->serializer->encode($projectionDetail->state),
            positions: $this->serializer->encode($projectionDetail->streamPositions),
            gaps: $this->serializer->encode($projectionDetail->streamGaps),
            lockedUntil: $this->lockManager->acquire()
        );
    }

    public function persistWhenLockThresholdIsReached(ProjectionDetail $projectionDetail, DateTimeImmutable $currentTime): void
    {
        if (! $this->canRefreshLock($currentTime)) {
            throw new RuntimeException('Cannot update projection lock with given time');
        }

        $this->provider->updateProjection(
            $this->streamName,
            positions: $this->serializer->encode($projectionDetail->streamPositions),
            gaps: $this->serializer->encode($projectionDetail->streamGaps),
            lockedUntil: $this->lockManager->refresh($currentTime)
        );
    }

    public function reset(ProjectionDetail $projectionDetail, ProjectionStatus $currentStatus): void
    {
        $this->provider->updateProjection(
            $this->streamName,
            status: $currentStatus->value,
            state: $this->serializer->encode($projectionDetail->state),
            positions: $this->serializer->encode($projectionDetail->streamPositions),
            gaps: $this->serializer->encode($projectionDetail->streamGaps),
        );
    }

    public function delete(bool $withEmittedEvents): void
    {
        $this->provider->deleteProjection($this->streamName);
    }

    public function loadDetail(): ProjectionDetail
    {
        $projection = $this->provider->retrieve($this->streamName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($this->streamName);
        }

        return new ProjectionDetail(
            $this->serializer->decode($projection->positions()),
            $this->serializer->decode($projection->state()),
            $this->serializer->decode($projection->gaps()),
        );
    }

    public function loadStatus(): ProjectionStatus
    {
        $projection = $this->provider->retrieve($this->streamName);

        if (! $projection instanceof ProjectionModel) {
            return ProjectionStatus::RUNNING;
        }

        return ProjectionStatus::from($projection->status());
    }

    public function canRefreshLock(DateTimeImmutable $currentTime): bool
    {
        return $this->lockManager->shouldRefresh($currentTime);
    }

    public function exists(): bool
    {
        return $this->provider->exists($this->streamName);
    }

    public function projectionName(): string
    {
        return $this->streamName;
    }
}
