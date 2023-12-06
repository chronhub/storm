<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\ProjectionStatus;

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

    public function start(ProjectionStatus $projectionStatus): void
    {
        $this->provider->acquireLock(
            $this->streamName,
            $projectionStatus->value,
            $this->lockManager->acquire(),
        );
    }

    public function stop(ProjectionDetail $projectionDetail, ProjectionStatus $projectionStatus): void
    {
        $this->persist($projectionDetail, $projectionStatus);
    }

    public function release(): void
    {
        $this->provider->updateProjection(
            $this->streamName,
            status: ProjectionStatus::IDLE->value,
            lockedUntil: null,
        );
    }

    public function startAgain(ProjectionStatus $projectionStatus): void
    {
        $this->provider->updateProjection(
            $this->streamName,
            status: $projectionStatus->value,
            lockedUntil: $this->lockManager->acquire(),
        );
    }

    public function persist(ProjectionDetail $projectionDetail, ?ProjectionStatus $projectionStatus): void
    {
        $this->provider->updateProjection(
            $this->streamName,
            status: $projectionStatus?->value,
            state: $this->serializer->encode($projectionDetail->state),
            position: $this->serializer->encode($projectionDetail->streamPositions),
            lockedUntil: $this->lockManager->refresh()
        );
    }

    public function reset(ProjectionDetail $projectionDetail, ProjectionStatus $currentStatus): void
    {
        $this->provider->updateProjection(
            $this->streamName,
            status: $currentStatus->value,
            state: $this->serializer->encode($projectionDetail->state),
            position: $this->serializer->encode($projectionDetail->streamPositions),
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
            $this->serializer->decode($projection->position()),
            $this->serializer->decode($projection->state()),
        );
    }

    public function updateLock(): void
    {
        if ($this->lockManager->shouldRefresh()) {
            $this->provider->updateProjection(
                $this->streamName,
                lockedUntil: $this->lockManager->refresh()
            );
        }
    }

    public function loadStatus(): ProjectionStatus
    {
        $projection = $this->provider->retrieve($this->streamName);

        if (! $projection instanceof ProjectionModel) {
            return ProjectionStatus::RUNNING;
        }

        return ProjectionStatus::from($projection->status());
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
