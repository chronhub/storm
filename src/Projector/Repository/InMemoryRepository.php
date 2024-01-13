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
use Chronhub\Storm\Projector\Repository\Data\CreateData;
use Chronhub\Storm\Projector\Repository\Data\PersistData;
use Chronhub\Storm\Projector\Repository\Data\ReleaseData;
use Chronhub\Storm\Projector\Repository\Data\ResetData;
use Chronhub\Storm\Projector\Repository\Data\StartAgainData;
use Chronhub\Storm\Projector\Repository\Data\StartData;
use Chronhub\Storm\Projector\Repository\Data\StopData;
use Chronhub\Storm\Projector\Repository\Data\UpdateLockData;

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
        $data = new CreateData($status->value);

        $this->provider->createProjection($this->projectionName(), $data);
    }

    public function start(ProjectionStatus $projectionStatus): void
    {
        $data = new StartData($projectionStatus->value, $this->lockManager->acquire());

        $this->provider->acquireLock($this->projectionName(), $data);
    }

    public function stop(ProjectionResult $projectionDetail, ProjectionStatus $projectionStatus): void
    {
        $data = new StopData(
            $projectionStatus->value,
            $this->serializer->encode($projectionDetail->userState),
            $this->serializer->encode($projectionDetail->checkpoints),
            $this->lockManager->refresh()
        );

        $this->updateProjection($data);
    }

    public function release(): void
    {
        $data = new ReleaseData(ProjectionStatus::IDLE->value, null);

        $this->updateProjection($data);
    }

    public function startAgain(ProjectionStatus $projectionStatus): void
    {
        $data = new StartAgainData($projectionStatus->value, $this->lockManager->acquire());

        $this->updateProjection($data);
    }

    public function persist(ProjectionResult $projectionDetail): void
    {
        $data = new PersistData(
            $this->serializer->encode($projectionDetail->userState),
            $this->serializer->encode($projectionDetail->checkpoints),
            $this->lockManager->refresh()
        );

        $this->updateProjection($data);
    }

    public function reset(ProjectionResult $projectionDetail, ProjectionStatus $currentStatus): void
    {
        $data = new ResetData(
            $currentStatus->value,
            $this->serializer->encode($projectionDetail->userState),
            $this->serializer->encode($projectionDetail->checkpoints),
        );

        $this->updateProjection($data);
    }

    public function delete(bool $withEmittedEvents): void
    {
        $this->provider->deleteProjection($this->streamName);
    }

    public function loadDetail(): ProjectionResult
    {
        $projection = $this->provider->retrieve($this->streamName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($this->streamName);
        }

        return new ProjectionResult(
            $this->serializer->decode($projection->checkpoint()),
            $this->serializer->decode($projection->state()),
        );
    }

    public function updateLock(): void
    {
        if ($this->lockManager->shouldRefresh()) {
            $data = new UpdateLockData($this->lockManager->refresh());

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
