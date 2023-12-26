<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionResult;

use function in_array;

trait InteractWithManagement
{
    public function tryUpdateLock(): void
    {
        $this->repository->updateLock();

        $this->notification->onSleepWhenEmptyBatchStreams();
    }

    public function freed(): void
    {
        $this->repository->release();

        $this->notification->onStatusChanged($this->notification->observeStatus(), ProjectionStatus::IDLE);
    }

    public function close(): void
    {
        $idleStatus = ProjectionStatus::IDLE;

        $this->repository->stop($this->getProjectionResult(), $idleStatus);

        $this->notification->onStatusChanged($this->notification->observeStatus(), $idleStatus);
        $this->notification->onProjectionStopped();
    }

    public function restart(): void
    {
        $this->notification->onProjectionRunning();

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->startAgain($runningStatus);

        $this->notification->onStatusChanged($this->notification->observeStatus(), $runningStatus);
    }

    public function disclose(): ProjectionStatus
    {
        $disclosedStatus = $this->repository->loadStatus();

        // checkMe: called from monitor remote status and persist the threshold.
        // till we use it actually to set the status,
        // but we cannot use it to query remote status.
        $this->notification->onStatusDisclosed($this->notification->observeStatus(), $disclosedStatus);

        return $disclosedStatus;
    }

    public function synchronise(): void
    {
        $projectionDetail = $this->repository->loadDetail();

        $this->notification->onCheckpointUpdated($projectionDetail->checkpoints);

        $state = $projectionDetail->userState;

        if ($state !== []) {
            $this->notification->onUserStateChanged($state);
        }
    }

    public function persistWhenCounterIsReached(): void
    {
        if ($this->notification->observeThresholdIsReached()) {
            $this->store();

            $this->notification->onEventReset();

            $this->disclose();

            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->notification->observeStatus(), $keepProjectionRunning, true)) {
                $this->notification->onProjectionStopped();
            }
        }
    }

    public function getName(): string
    {
        return $this->repository->projectionName();
    }

    public function getCurrentStreamName(): string
    {
        return $this->notification->observeStreamName();
    }

    protected function mountProjection(): void
    {
        $this->notification->onProjectionRunning();

        if (! $this->repository->exists()) {
            $this->repository->create($this->notification->observeStatus());
        }

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->start($runningStatus);

        $this->notification->onStatusChanged($this->notification->observeStatus(), $runningStatus);
    }

    protected function getProjectionResult(): ProjectionResult
    {
        return new ProjectionResult(
            $this->notification->observeCheckpoints(),
            $this->notification->observeUserState()
        );
    }
}
