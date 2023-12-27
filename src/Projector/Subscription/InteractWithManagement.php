<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionResult;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointUpdated;
use Chronhub\Storm\Projector\Subscription\Notification\EventReset;
use Chronhub\Storm\Projector\Subscription\Notification\GetCheckpoints;
use Chronhub\Storm\Projector\Subscription\Notification\GetStatus;
use Chronhub\Storm\Projector\Subscription\Notification\GetStreamName;
use Chronhub\Storm\Projector\Subscription\Notification\GetUserState;
use Chronhub\Storm\Projector\Subscription\Notification\IsEventReached;
use Chronhub\Storm\Projector\Subscription\Notification\SleepWhenEmptyBatchStreams;
use Chronhub\Storm\Projector\Subscription\Notification\SprintRunning;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Notification\StatusChanged;
use Chronhub\Storm\Projector\Subscription\Notification\StatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateChanged;

use function in_array;

trait InteractWithManagement
{
    public function tryUpdateLock(): void
    {
        $this->repository->updateLock();

        $this->task->listen(SleepWhenEmptyBatchStreams::class);
    }

    public function freed(): void
    {
        $this->repository->release();

        $this->task->listen(
            StatusChanged::class,
            $this->task->listen(GetStatus::class), ProjectionStatus::IDLE
        );
    }

    public function close(): void
    {
        $idleStatus = ProjectionStatus::IDLE;

        $this->repository->stop($this->getProjectionResult(), $idleStatus);

        $this->task->listen(
            StatusChanged::class,
            $this->task->listen(GetStatus::class), $idleStatus
        );

        $this->task->listen(SprintStopped::class);
    }

    public function restart(): void
    {
        $this->task->listen(SprintRunning::class);

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->startAgain($runningStatus);

        $this->task->listen(
            StatusChanged::class,
            $this->task->listen(GetStatus::class), $runningStatus
        );
    }

    public function disclose(): void
    {
        $disclosedStatus = $this->repository->loadStatus();

        $this->task->listen(
            StatusDisclosed::class,
            $this->task->listen(GetStatus::class), $disclosedStatus
        );
    }

    public function synchronise(): void
    {
        $projectionDetail = $this->repository->loadDetail();

        $this->task->listen(CheckpointUpdated::class, $projectionDetail->checkpoints);

        $state = $projectionDetail->userState;

        if ($state !== []) {
            $this->task->listen(UserStateChanged::class, $state);
        }
    }

    public function persistWhenCounterIsReached(): void
    {
        if ($this->task->listen(IsEventReached::class)) {
            $this->store();

            $this->task->listen(EventReset::class);

            $this->disclose();

            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->task->listen(GetStatus::class), $keepProjectionRunning, true)) {
                $this->task->listen(SprintStopped::class);
            }
        }
    }

    public function getName(): string
    {
        return $this->repository->projectionName();
    }

    public function getCurrentStreamName(): string
    {
        return $this->task->listen(GetStreamName::class);
    }

    public function hub(): HookHub
    {
        return $this->task;
    }

    protected function mountProjection(): void
    {
        $this->task->listen(SprintRunning::class);

        if (! $this->repository->exists()) {
            $this->repository->create($this->task->listen(GetStatus::class));
        }

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->start($runningStatus);

        $this->task->listen(
            StatusChanged::class,
            $this->task->listen(GetStatus::class), $runningStatus
        );
    }

    protected function getProjectionResult(): ProjectionResult
    {
        return new ProjectionResult(
            $this->task->listen(GetCheckpoints::class),
            $this->task->listen(GetUserState::class)
        );
    }
}
