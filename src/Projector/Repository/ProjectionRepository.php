<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\ProjectionStatus;

final class ProjectionRepository implements ProjectionRepositoryInterface
{
    public function __construct(
        public ProjectionProvider $provider,
        public LockManager $lockManager,
        public JsonSerializer $serializer,
        public string $streamName
    ) {
    }

    public function create(ProjectionStatus $status): bool
    {
        return $this->provider->createProjection($this->streamName, $status->value);
    }

    public function stop(array $streamPositions, array $state): bool
    {
        if (! $this->persist($streamPositions, $state)) {
            return false;
        }

        $idleProjection = ProjectionStatus::IDLE;

        return $this->updateProjection(['status' => $idleProjection->value]);
    }

    public function startAgain(): bool
    {
        $runningStatus = ProjectionStatus::RUNNING;

        return $this->updateProjection([
            'status' => $runningStatus->value,
            'locked_until' => $this->lockManager->acquire(),
        ]);
    }

    public function persist(array $streamPositions, array $state): bool
    {
        return $this->updateprojection([
            'position' => $this->serializer->encode($streamPositions),
            'state' => $this->serializer->encode($state),
            'locked_until' => $this->lockManager->refresh(),
        ]);
    }

    public function reset(array $streamPositions, array $state, ProjectionStatus $currentStatus): bool
    {
        return $this->updateProjection([
            'position' => $this->serializer->encode($streamPositions),
            'state' => $this->serializer->encode($state),
            'status' => $currentStatus->value,
        ]);
    }

    public function delete(): bool
    {
        return $this->provider->deleteProjection($this->streamName);
    }

    public function loadState(): array
    {
        //fixMe method name is not correct return state and positions
        $projection = $this->provider->retrieve($this->streamName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($this->streamName);
        }

        return [
            $this->serializer->decode($projection->position()),
            $this->serializer->decode($projection->state()),
        ];
    }

    public function loadStatus(): ProjectionStatus
    {
        $projection = $this->provider->retrieve($this->streamName);

        if (! $projection instanceof ProjectionModel) {
            return ProjectionStatus::RUNNING;
        }

        return ProjectionStatus::from($projection->status());
    }

    public function acquireLock(): bool
    {
        $runningProjection = ProjectionStatus::RUNNING;

        return $this->provider->acquireLock(
            $this->streamName,
            $runningProjection->value,
            $this->lockManager->acquire(),
            $this->lockManager->current(),
        );
    }

    public function updateLock(array $streamPositions): bool
    {
        if ($this->lockManager->tryUpdate()) {
            return $this->updateProjection([
                'position' => $this->serializer->encode($streamPositions),
                'locked_until' => $this->lockManager->increment(),
            ]);
        }

        return true;
    }

    public function releaseLock(): bool
    {
        $idleProjection = ProjectionStatus::IDLE;

        return $this->updateProjection([
            'status' => $idleProjection->value,
            'locked_until' => null,
        ]);
    }

    public function exists(): bool
    {
        return $this->provider->exists($this->streamName);
    }

    public function projectionName(): string
    {
        return $this->streamName;
    }

    private function updateProjection(array $data): bool
    {
        return $this->provider->updateProjection($this->streamName, $data);
    }
}
