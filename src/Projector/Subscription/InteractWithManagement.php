<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionResult;
use Chronhub\Storm\Projector\Stream\Checkpoint;
use Chronhub\Storm\Projector\Subscription\Batch\BatchReset;
use Chronhub\Storm\Projector\Subscription\Batch\IsBatchReached;
use Chronhub\Storm\Projector\Subscription\Checkpoint\CheckpointReset;
use Chronhub\Storm\Projector\Subscription\Checkpoint\CheckpointUpdated;
use Chronhub\Storm\Projector\Subscription\Checkpoint\CurrentCheckpoint;
use Chronhub\Storm\Projector\Subscription\Checkpoint\SnapshotTaken;
use Chronhub\Storm\Projector\Subscription\Sprint\SprintContinue;
use Chronhub\Storm\Projector\Subscription\Sprint\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Status\CurrentStatus;
use Chronhub\Storm\Projector\Subscription\Status\StatusChanged;
use Chronhub\Storm\Projector\Subscription\Status\StatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Stream\CurrentProcessedStream;
use Chronhub\Storm\Projector\Subscription\UserState\CurrentUserState;
use Chronhub\Storm\Projector\Subscription\UserState\UserStateChanged;
use Chronhub\Storm\Projector\Subscription\UserState\UserStateRestored;

use function in_array;

trait InteractWithManagement
{
    public function tryUpdateLock(): void
    {
        $this->projectionRepository->updateLock();
    }

    public function freed(): void
    {
        $this->projectionRepository->release();

        $this->onStatusChanged(ProjectionStatus::IDLE);
    }

    public function close(): void
    {
        $idleStatus = ProjectionStatus::IDLE;

        $this->projectionRepository->stop($this->getProjectionResult(), $idleStatus);

        $this->onStatusChanged($idleStatus);

        $this->hub->expect(SprintStopped::class);
    }

    public function restart(): void
    {
        $this->hub->notify(SprintContinue::class);

        $runningStatus = ProjectionStatus::RUNNING;

        $this->projectionRepository->startAgain($runningStatus);

        $this->onStatusChanged($runningStatus);
    }

    public function disclose(): void
    {
        $disclosedStatus = $this->projectionRepository->loadStatus();

        $this->hub->notify(
            StatusDisclosed::class,
            $this->hub->expect(CurrentStatus::class), $disclosedStatus
        );
    }

    public function synchronise(): void
    {
        $projectionDetail = $this->projectionRepository->loadDetail();

        $this->hub->notify(CheckpointUpdated::class, $projectionDetail->checkpoints);

        $state = $projectionDetail->userState;

        $this->hub->notifyWhen(
            $state !== [],
            fn (NotificationHub $hub) => $hub->notify(UserStateChanged::class, $state)
        );
    }

    public function persistWhenThresholdIsReached(): void
    {
        if ($this->hub->expect(IsBatchReached::class)) {
            $this->store();

            $this->hub->notify(BatchReset::class);

            $this->disclose();

            // todo check if Idle still needed
            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->hub->expect(CurrentStatus::class), $keepProjectionRunning, true)) {
                $this->hub->notify(SprintStopped::class);
            }
        }
    }

    public function snapshot(Checkpoint $checkpoint): void
    {
        $this->snapshotRepository->snapshot($this->getName(), $checkpoint);

        $this->hub->notify(SnapshotTaken::class, $checkpoint);

        dump($checkpoint);
    }

    public function getName(): string
    {
        return $this->projectionRepository->projectionName();
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

        if (! $this->projectionRepository->exists()) {
            $this->projectionRepository->create($this->hub->expect(CurrentStatus::class));
        }

        $runningStatus = ProjectionStatus::RUNNING;

        $this->projectionRepository->start($runningStatus);

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
            $this->hub->expect(CurrentCheckpoint::class),
            $this->hub->expect(CurrentUserState::class)
        );
    }
}
