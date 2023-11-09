<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\ProjectionStatus;

use function array_merge;

// todo add gaps field to migration
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

    public function stop(ProjectionDetail $projectionDetail): bool
    {
        if (! $this->persist($projectionDetail)) {
            return false;
        }

        return $this->provider->updateProjection(
            $this->streamName,
            ['status' => ProjectionStatus::IDLE->value]
        );
    }

    public function startAgain(): bool
    {
        return $this->provider->updateProjection(
            $this->streamName,
            [
                'status' => ProjectionStatus::RUNNING->value,
                'locked_until' => $this->lockManager->acquire(),
            ]
        );
    }

    public function persist(ProjectionDetail $projectionDetail): bool
    {
        return $this->provider->updateProjection(
            $this->streamName,
            array_merge(
                $this->encodeProjectionDetail($projectionDetail),
                ['locked_until' => $this->lockManager->refresh()]
            )
        );
    }

    public function reset(ProjectionDetail $projectionDetail, ProjectionStatus $currentStatus): bool
    {
        return $this->provider->updateProjection(
            $this->streamName,
            array_merge(
                $this->encodeProjectionDetail($projectionDetail),
                ['status' => $currentStatus->value]
            )
        );
    }

    public function delete(): bool
    {
        return $this->provider->deleteProjection($this->streamName);
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

    public function acquireLock(): bool
    {
        return $this->provider->acquireLock(
            $this->streamName,
            ProjectionStatus::RUNNING->value,
            // todo fix this
            $this->lockManager->acquire(),
            $this->lockManager->current(),
        );
    }

    public function updateLock(array $streamPositions): bool
    {
        if ($this->lockManager->tryUpdate()) {
            return $this->provider->updateProjection(
                $this->streamName,
                [
                    'position' => $this->serializer->encode($streamPositions),
                    'locked_until' => $this->lockManager->increment(),
                ]
            );
        }

        return true;
    }

    public function releaseLock(): bool
    {
        return $this->provider->updateProjection(
            $this->streamName,
            [
                'status' => ProjectionStatus::IDLE->value,
                'locked_until' => null,
            ]
        );
    }

    public function exists(): bool
    {
        return $this->provider->exists($this->streamName);
    }

    public function projectionName(): string
    {
        return $this->streamName;
    }

    /**
     * @return array{position: string, state: string, gaps: string}
     */
    private function encodeProjectionDetail(ProjectionDetail $projectionDetail): array
    {
        return [
            'position' => $this->serializer->encode($projectionDetail->streamPositions),
            'state' => $this->serializer->encode($projectionDetail->state),
            'gaps' => $this->serializer->encode($projectionDetail->streamGaps),
        ];
    }
}
