<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Contracts\Projector\Store;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use function is_array;

final class StandaloneStore implements Store
{
    public function __construct(public Context $context,
                                public ProjectionProvider $projectionProvider,
                                public RepositoryLock $repositoryLock,
                                public JsonSerializer $serializer,
                                public string $streamName)
    {
    }

    public function create(): bool
    {
        return $this->projectionProvider->createProjection(
            $this->streamName,
            $this->context->status->value
        );
    }

    public function loadState(): bool
    {
        $projection = $this->projectionProvider->retrieve($this->streamName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($this->streamName);
        }

        $this->context->streamPosition->discover($this->serializer->decode($projection->position()));

        $state = $this->serializer->decode($projection->state());

        if (is_array($state) && ! empty($state)) {
            $this->context->state->put($state);
        }

        return true;
    }

    public function stop(): bool
    {
        $this->persist();

        $this->context->runner->stop(true);

        $idleProjection = ProjectionStatus::IDLE;

        $stopped = $this->updateProjection(['status' => $idleProjection->value]);

        if (! $stopped) {
            return false;
        }

        $this->context->status = $idleProjection;

        return true;
    }

    public function startAgain(): bool
    {
        $this->context->runner->stop(false);

        $runningStatus = ProjectionStatus::RUNNING;

        $restarted = $this->updateProjection(
            [
                'status' => $runningStatus->value,
                'locked_until' => $this->repositoryLock->acquire(),
            ]
        );

        if (! $restarted) {
            return false;
        }

        $this->context->status = $runningStatus;

        return true;
    }

    public function persist(): bool
    {
        return $this->updateprojection(
            [
                'position' => $this->serializer->encode($this->context->streamPosition->all()),
                'state' => $this->serializer->encode($this->context->state->get()),
                'locked_until' => $this->repositoryLock->refresh(),
            ]
        );
    }

    public function reset(): bool
    {
        $this->context->streamPosition->reset();

        $this->context->resetStateWithInitialize();

        return $this->updateProjection(
            [
                'position' => $this->serializer->encode($this->context->streamPosition->all()),
                'state' => $this->serializer->encode($this->context->state->get()),
                'status' => $this->context->status->value,
            ]
        );
    }

    public function delete(bool $withEmittedEvents): bool
    {
        $deleted = $this->projectionProvider->deleteProjection($this->streamName);

        if (! $deleted) {
            return false;
        }

        $this->context->runner->stop(true);

        $this->context->resetStateWithInitialize();

        $this->context->streamPosition->reset();

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
            $this->repositoryLock->acquire(),
            $this->repositoryLock->current(),
        );

        if (! $acquired) {
            return false;
        }

        $this->context->status = $runningProjection;

        return true;
    }

    public function updateLock(): bool
    {
        if ($this->repositoryLock->tryUpdate()) {
            return $this->updateProjection(
                [
                    'locked_until' => $this->repositoryLock->increment(),
                    'position' => $this->serializer->encode($this->context->streamPosition->all()),
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

        $this->context->status = $idleProjection;

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
