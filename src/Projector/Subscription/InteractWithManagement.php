<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionResult;
use Chronhub\Storm\Projector\Subscription\Notification\BatchSleep;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointReset;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointUpdated;
use Chronhub\Storm\Projector\Subscription\Notification\EventReset;
use Chronhub\Storm\Projector\Subscription\Notification\GetCheckpoints;
use Chronhub\Storm\Projector\Subscription\Notification\GetStatus;
use Chronhub\Storm\Projector\Subscription\Notification\GetStreamName;
use Chronhub\Storm\Projector\Subscription\Notification\GetUserState;
use Chronhub\Storm\Projector\Subscription\Notification\IsEventReached;
use Chronhub\Storm\Projector\Subscription\Notification\SprintRunning;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Notification\StatusChanged;
use Chronhub\Storm\Projector\Subscription\Notification\StatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateChanged;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateResetAgain;

use function in_array;

trait InteractWithManagement
{
    public function tryUpdateLock(): void
    {
        $this->repository->updateLock();

        $this->hub->interact(BatchSleep::class);
    }

    public function freed(): void
    {
        $this->repository->release();

        $this->onStatusChanged(ProjectionStatus::IDLE);
    }

    public function close(): void
    {
        $idleStatus = ProjectionStatus::IDLE;

        $this->repository->stop($this->getProjectionResult(), $idleStatus);

        $this->onStatusChanged($idleStatus);

        $this->hub->interact(SprintStopped::class);
    }

    public function restart(): void
    {
        $this->hub->interact(SprintRunning::class);

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->startAgain($runningStatus);

        $this->onStatusChanged($runningStatus);
    }

    public function disclose(): void
    {
        $disclosedStatus = $this->repository->loadStatus();

        $this->hub->interact(
            StatusDisclosed::class,
            $this->hub->interact(GetStatus::class), $disclosedStatus
        );
    }

    public function synchronise(): void
    {
        $projectionDetail = $this->repository->loadDetail();

        $this->hub->interact(CheckpointUpdated::class, $projectionDetail->checkpoints);

        $state = $projectionDetail->userState;

        if ($state !== []) {
            $this->hub->interact(UserStateChanged::class, $state);
        }
    }

    public function persistWhenCounterIsReached(): void
    {
        if ($this->hub->interact(IsEventReached::class)) {
            $this->store();

            $this->hub->interact(EventReset::class);

            $this->disclose();

            // todo check if Idle still needed
            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->hub->interact(GetStatus::class), $keepProjectionRunning, true)) {
                $this->hub->interact(SprintStopped::class);
            }
        }
    }

    public function getName(): string
    {
        return $this->repository->projectionName();
    }

    public function getCurrentStreamName(): string
    {
        return $this->hub->interact(GetStreamName::class);
    }

    public function hub(): HookHub
    {
        return $this->hub;
    }

    protected function mountProjection(): void
    {
        $this->hub->interact(SprintRunning::class);

        if (! $this->repository->exists()) {
            $this->repository->create($this->hub->interact(GetStatus::class));
        }

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->start($runningStatus);

        $this->onStatusChanged($runningStatus);
    }

    protected function resetState(): void
    {
        $this->hub->interact(CheckpointReset::class);
        $this->hub->interact(UserStateResetAgain::class);
    }

    protected function onStatusChanged(ProjectionStatus $status): void
    {
        $this->hub->interact(
            StatusChanged::class,
            $this->hub->interact(GetStatus::class), $status
        );
    }

    protected function getProjectionResult(): ProjectionResult
    {
        return new ProjectionResult(
            $this->hub->interact(GetCheckpoints::class),
            $this->hub->interact(GetUserState::class)
        );
    }
}
