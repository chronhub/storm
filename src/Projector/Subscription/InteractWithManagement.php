<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionResult;
use Chronhub\Storm\Projector\Subscription\Notification\BatchCounterReset;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointReset;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointUpdated;
use Chronhub\Storm\Projector\Subscription\Notification\CurrentCheckpoints;
use Chronhub\Storm\Projector\Subscription\Notification\CurrentProcessedStream;
use Chronhub\Storm\Projector\Subscription\Notification\CurrentStatus;
use Chronhub\Storm\Projector\Subscription\Notification\CurrentUserState;
use Chronhub\Storm\Projector\Subscription\Notification\IsBatchCounterReached;
use Chronhub\Storm\Projector\Subscription\Notification\SprintContinue;
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
        $this->hub->notify(SprintContinue::class);

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->startAgain($runningStatus);

        $this->onStatusChanged($runningStatus);
    }

    public function disclose(): void
    {
        $disclosedStatus = $this->repository->loadStatus();

        $this->hub->notify(
            StatusDisclosed::class,
            $this->hub->expect(CurrentStatus::class), $disclosedStatus
        );
    }

    public function synchronise(): void
    {
        $projectionDetail = $this->repository->loadDetail();

        $this->hub->notify(CheckpointUpdated::class, $projectionDetail->checkpoints);

        $state = $projectionDetail->userState;

        $this->hub->notifyWhen($state !== [], fn (NotificationHub $hub) => $hub->notify(UserStateChanged::class, $state));
    }

    public function persistWhenThresholdIsReached(): void
    {
        if ($this->hub->expect(IsBatchCounterReached::class)) {
            $this->store();

            $this->hub->notify(BatchCounterReset::class);

            $this->disclose();

            // todo check if Idle still needed
            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->hub->expect(CurrentStatus::class), $keepProjectionRunning, true)) {
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
        return $this->hub->expect(CurrentProcessedStream::class);
    }

    public function hub(): NotificationHub
    {
        return $this->hub;
    }

    protected function mountProjection(): void
    {
        $this->hub->notify(SprintContinue::class);

        if (! $this->repository->exists()) {
            $this->repository->create($this->hub->expect(CurrentStatus::class));
        }

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->start($runningStatus);

        $this->onStatusChanged($runningStatus);
    }

    protected function resetState(): void
    {
        $this->hub->notifyMany(CheckpointReset::class, UserStateRestored::class);
    }

    protected function onStatusChanged(ProjectionStatus $status): void
    {
        $this->hub->notify(
            StatusChanged::class,
            $this->hub->expect(CurrentStatus::class), $status
        );
    }

    protected function getProjectionResult(): ProjectionResult
    {
        return new ProjectionResult(
            $this->hub->expect(CurrentCheckpoints::class),
            $this->hub->expect(CurrentUserState::class)
        );
    }
}
