<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionDetail;

use function in_array;

trait InteractWithManagement
{
    public function update(): void
    {
        $this->repository->updateLock();
    }

    public function freed(): void
    {
        $this->repository->release();

        $this->subscription->setStatus(ProjectionStatus::IDLE);
    }

    public function close(): void
    {
        $idleStatus = ProjectionStatus::IDLE;

        $this->repository->stop($this->getProjectionDetail(), $idleStatus);

        $this->subscription->setStatus($idleStatus);

        $this->subscription->sprint->stop();
    }

    public function restart(): void
    {
        $this->subscription->sprint->continue();

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->startAgain($runningStatus);

        $this->subscription->setStatus($runningStatus);
    }

    public function disclose(): ProjectionStatus
    {
        return $this->repository->loadStatus();
    }

    public function synchronise(): void
    {
        $projectionDetail = $this->repository->loadDetail();

        $this->subscription->streamManager->merge($projectionDetail->streamPositions);

        $state = $projectionDetail->state;

        if ($state !== []) {
            $this->subscription->state->put($state);
        }
    }

    public function persistWhenCounterIsReached(): void
    {
        if ($this->subscription->eventCounter->isReached()) {
            $this->store();

            $this->subscription->eventCounter->reset();

            $this->subscription->setStatus($this->disclose());

            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->subscription->currentStatus(), $keepProjectionRunning, true)) {
                $this->subscription->sprint->stop();
            }
        }
    }

    public function getName(): string
    {
        return $this->repository->projectionName();
    }

    public function getClock(): SystemClock
    {
        return $this->subscription->clock;
    }

    public function getCurrentStreamName(): string
    {
        return $this->subscription->currentStreamName();
    }

    protected function mountProjection(): void
    {
        $this->subscription->sprint->continue();

        if (! $this->repository->exists()) {
            $this->repository->create(
                $this->subscription->currentStatus()
            );
        }

        $status = ProjectionStatus::RUNNING;

        $this->repository->start($status);

        $this->subscription->setStatus($status);
    }

    protected function getProjectionDetail(): ProjectionDetail
    {
        $streamPositions = $this->subscription->streamManager->jsonSerialize();

        return new ProjectionDetail($streamPositions, $this->subscription->state->get());
    }
}
