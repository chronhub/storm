<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Contracts\Projector\ProjectionData;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\Mapper\CreateDataDTO;
use Chronhub\Storm\Projector\Repository\Mapper\PersistDataDTO;
use Chronhub\Storm\Projector\Repository\Mapper\ReleaseDataDTO;
use Chronhub\Storm\Projector\Repository\Mapper\ResetDataDTO;
use Chronhub\Storm\Projector\Repository\Mapper\StartAgainDataDTO;
use Chronhub\Storm\Projector\Repository\Mapper\StartDataDTO;
use Chronhub\Storm\Projector\Repository\Mapper\StopDataDTO;
use Chronhub\Storm\Projector\Repository\Mapper\UpdateLockDataDTO;

// add datetime created_at, stopped_at, updated_at, reset_at, deleted_at, deleted_with_emitted_events_at
// which should be handled by a ProjectionTimeTracker for storage, log, db, etc...
// we should also add here the time, according to the operation.
// but require three migration tables, one for projection with/without times, one for time tracking

// todo add transformer to transform from/to array which hold the jsonSerializer
// we also need to change type position and state to accept array in projection model
final readonly class InMemoryRepository implements ProjectionRepository
{
    public function __construct(
        private ProjectionProvider $provider,
        private LockManager $lockManager,
        private JsonSerializer $serializer,
        private string $streamName
    ) {
    }

    public function create(ProjectionStatus $status): void
    {
        $data = new CreateDataDTO($status->value);

        $this->provider->createProjection($this->projectionName(), $data);
    }

    public function start(ProjectionStatus $projectionStatus): void
    {
        $data = new StartDataDTO($projectionStatus->value, $this->lockManager->acquire());

        $this->provider->acquireLock($this->projectionName(), $data);
    }

    public function stop(ProjectionDetail $projectionDetail, ProjectionStatus $projectionStatus): void
    {
        $data = new StopDataDTO(
            $projectionStatus->value,
            $this->serializer->encode($projectionDetail->state),
            $this->serializer->encode($projectionDetail->streamPositions),
            $this->lockManager->refresh()
        );

        $this->updateProjection($data);
    }

    public function release(): void
    {
        $data = new ReleaseDataDTO(ProjectionStatus::IDLE->value, null);

        $this->updateProjection($data);
    }

    public function startAgain(ProjectionStatus $projectionStatus): void
    {
        $data = new StartAgainDataDTO($projectionStatus->value, $this->lockManager->acquire());

        $this->updateProjection($data);
    }

    public function persist(ProjectionDetail $projectionDetail): void
    {
        $data = new PersistDataDTO(
            $this->serializer->encode($projectionDetail->state),
            $this->serializer->encode($projectionDetail->streamPositions),
            $this->lockManager->refresh()
        );

        $this->updateProjection($data);
    }

    public function reset(ProjectionDetail $projectionDetail, ProjectionStatus $currentStatus): void
    {
        $data = new ResetDataDTO(
            $currentStatus->value,
            $this->serializer->encode($projectionDetail->state),
            $this->serializer->encode($projectionDetail->streamPositions),
        );

        $this->updateProjection($data);
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
            $data = new UpdateLockDataDTO($this->lockManager->refresh());

            $this->updateProjection($data);
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

    private function updateProjection(ProjectionData $data): void
    {
        $this->provider->updateProjection($this->streamName, $data);
    }
}
