<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionResult;
use Chronhub\Storm\Projector\Subscription\Notification\EventReset;

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
        $this->subscriptor->setStatus(ProjectionStatus::IDLE);
    }

    public function close(): void
    {
        $idleStatus = ProjectionStatus::IDLE;

        $this->repository->stop($this->getProjectionResult(), $idleStatus);
        $this->subscriptor->setStatus($idleStatus);
        $this->subscriptor->stop();
    }

    public function restart(): void
    {
        $this->subscriptor->continue();

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->startAgain($runningStatus);
        $this->subscriptor->setStatus($runningStatus);
    }

    public function disclose(): ProjectionStatus
    {
        return $this->repository->loadStatus();
    }

    public function synchronise(): void
    {
        $projectionDetail = $this->repository->loadDetail();

        $this->subscriptor->updateCheckpoints($projectionDetail->checkpoints);

        $state = $projectionDetail->userState;

        if ($state !== []) {
            $this->subscriptor->setUserState($state);
        }
    }

    public function persistWhenCounterIsReached(): void
    {
        if ($this->subscriptor->isEventReached()) {
            $this->store();

            $this->subscriptor->notify(new EventReset());
            $this->subscriptor->setStatus($this->disclose());

            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->subscriptor->currentStatus(), $keepProjectionRunning, true)) {
                $this->subscriptor->stop();
            }
        }
    }

    public function getName(): string
    {
        return $this->repository->projectionName();
    }

    public function getClock(): SystemClock
    {
        return $this->subscriptor->clock();
    }

    public function getCurrentStreamName(): string
    {
        return $this->subscriptor->getStreamName();
    }

    protected function mountProjection(): void
    {
        $this->subscriptor->continue();

        if (! $this->repository->exists()) {
            $this->repository->create($this->subscriptor->currentStatus());
        }

        $status = ProjectionStatus::RUNNING;

        $this->repository->start($status);
        $this->subscriptor->setStatus($status);
    }

    protected function getProjectionResult(): ProjectionResult
    {
        $streamPositions = $this->subscriptor->checkPoints();

        return new ProjectionResult($streamPositions, $this->subscriptor->getUserState());
    }
}
