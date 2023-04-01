<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionStore;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use function is_array;

final class StandaloneProjectionStore implements ProjectionStore
{
    public function __construct(
        public Subscription $subscription,
        public ProjectionProvider $projectionProvider,
        public LockManager $lockManager,
        public JsonSerializer $serializer,
        public string $streamName
    ) {
    }

    public function create(): bool
    {
        return $this->projectionProvider->createProjection(
            $this->streamName,
            $this->subscription->status->value
        );
    }

    public function stop(): bool
    {
        $this->persist();

        $this->subscription->sprint()->stop();

        $idleProjection = ProjectionStatus::IDLE;

        $stopped = $this->updateProjection(['status' => $idleProjection->value]);

        if (! $stopped) {
            return false;
        }

        $this->subscription->status = $idleProjection;

        return true;
    }

    public function startAgain(): bool
    {
        $this->subscription->sprint()->stop();

        $runningStatus = ProjectionStatus::RUNNING;

        $restarted = $this->updateProjection(
            [
                'status' => $runningStatus->value,
                'locked_until' => $this->lockManager->acquire(),
            ]
        );

        if (! $restarted) {
            return false;
        }

        $this->subscription->status = $runningStatus;

        return true;
    }

    public function persist(): bool
    {
        return $this->updateprojection(
            [
                'position' => $this->serializer->encode($this->subscription->streamPosition()->all()),
                'state' => $this->serializer->encode($this->subscription->state()->get()),
                'locked_until' => $this->lockManager->refresh(),
            ]
        );
    }

    public function reset(): bool
    {
        $this->subscription->streamPosition()->reset();

        $this->subscription->initializeAgain();

        return $this->updateProjection(
            [
                'position' => $this->serializer->encode($this->subscription->streamPosition()->all()),
                'state' => $this->serializer->encode($this->subscription->state()->get()),
                'status' => $this->subscription->status->value,
            ]
        );
    }

    public function delete(bool $withEmittedEvents): bool
    {
        $deleted = $this->projectionProvider->deleteProjection($this->streamName);

        if (! $deleted) {
            return false;
        }

        $this->subscription->sprint()->stop();

        $this->subscription->initializeAgain();

        $this->subscription->streamPosition()->reset();

        return true;
    }

    public function loadState(): bool
    {
        $projection = $this->projectionProvider->retrieve($this->streamName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($this->streamName);
        }

        $this->subscription->streamPosition()->discover($this->serializer->decode($projection->position()));

        $state = $this->serializer->decode($projection->state());

        if (is_array($state) && ! empty($state)) {
            $this->subscription->state->put($state);
        }

        return true;
    }

    public function loadStatus(): ProjectionStatus
    {
        $projection = $this->projectionProvider->retrieve($this->streamName);

        if (! $projection instanceof ProjectionModel) {
            return ProjectionStatus::RUNNING;
        }

        return ProjectionStatus::from($projection->status());
    }

    public function acquireLock(): bool
    {
        $runningProjection = ProjectionStatus::RUNNING;

        $acquired = $this->projectionProvider->acquireLock(
            $this->streamName,
            $runningProjection->value,
            $this->lockManager->acquire(),
            $this->lockManager->current(),
        );

        if (! $acquired) {
            return false;
        }

        $this->subscription->status = $runningProjection;

        return true;
    }

    public function updateLock(): bool
    {
        if ($this->lockManager->tryUpdate()) {
            return $this->updateProjection(
                [
                    'locked_until' => $this->lockManager->increment(),
                    'position' => $this->serializer->encode($this->subscription->streamPosition()->all()),
                ]
            );
        }

        return true;
    }

    public function releaseLock(): bool
    {
        $idleProjection = ProjectionStatus::IDLE;

        $released = $this->updateProjection(
            [
                'status' => $idleProjection->value,
                'locked_until' => null,
            ]
        );

        if (! $released) {
            return false;
        }

        $this->subscription->status = $idleProjection;

        return true;
    }

    public function exists(): bool
    {
        return $this->projectionProvider->projectionExists($this->streamName);
    }

    public function currentStreamName(): string
    {
        return $this->streamName;
    }

    private function updateProjection(array $data): bool
    {
        return $this->projectionProvider->updateProjection($this->streamName, $data);
    }
}
