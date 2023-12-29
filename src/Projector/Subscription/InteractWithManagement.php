<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionResult;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointReset;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointUpdated;
use Chronhub\Storm\Projector\Subscription\Notification\EventCounterReset;
use Chronhub\Storm\Projector\Subscription\Notification\GetCheckpoints;
use Chronhub\Storm\Projector\Subscription\Notification\GetProcessedStream;
use Chronhub\Storm\Projector\Subscription\Notification\GetStatus;
use Chronhub\Storm\Projector\Subscription\Notification\GetUserState;
use Chronhub\Storm\Projector\Subscription\Notification\IsEventCounterReached;
use Chronhub\Storm\Projector\Subscription\Notification\SprintRunning;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Notification\StatusChanged;
use Chronhub\Storm\Projector\Subscription\Notification\StatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateChanged;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateRestored;

use function in_array;

trait InteractWithManagement
{
    public function tryUpdateLock(): void
    {
        $this->repository->updateLock();
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

        $this->hub->expect(SprintStopped::class);
    }

    public function restart(): void
    {
        $this->hub->notify(SprintRunning::class);

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->startAgain($runningStatus);

        $this->onStatusChanged($runningStatus);
    }

    public function disclose(): void
    {
        $disclosedStatus = $this->repository->loadStatus();

        $this->hub->notify(
            StatusDisclosed::class,
            $this->hub->expect(GetStatus::class), $disclosedStatus
        );
    }

    public function synchronise(): void
    {
        $projectionDetail = $this->repository->loadDetail();

        $this->hub->notify(CheckpointUpdated::class, $projectionDetail->checkpoints);

        $state = $projectionDetail->userState;

        if ($state !== []) {
            $this->hub->notify(UserStateChanged::class, $state);
        }
    }

    public function persistWhenThresholdIsReached(): void
    {
        if ($this->hub->expect(IsEventCounterReached::class)) {
            $this->store();

            $this->hub->notify(EventCounterReset::class);

            $this->disclose();

            // todo check if Idle still needed
            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->hub->expect(GetStatus::class), $keepProjectionRunning, true)) {
                $this->hub->notify(SprintStopped::class);
            }
        }
    }

    public function getName(): string
    {
        return $this->repository->projectionName();
    }

    public function getProcessedStream(): string
    {
        return $this->hub->expect(GetProcessedStream::class);
    }

    public function hub(): HookHub
    {
        return $this->hub;
    }

    protected function mountProjection(): void
    {
        $this->hub->notify(SprintRunning::class);

        if (! $this->repository->exists()) {
            $this->repository->create($this->hub->expect(GetStatus::class));
        }

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->start($runningStatus);

        $this->onStatusChanged($runningStatus);
    }

    protected function resetState(): void
    {
        $this->hub->notify(CheckpointReset::class);
        $this->hub->notify(UserStateRestored::class);
    }

    protected function onStatusChanged(ProjectionStatus $status): void
    {
        $this->hub->notify(
            StatusChanged::class,
            $this->hub->expect(GetStatus::class), $status
        );
    }

    protected function getProjectionResult(): ProjectionResult
    {
        return new ProjectionResult(
            $this->hub->expect(GetCheckpoints::class),
            $this->hub->expect(GetUserState::class)
        );
    }
}
