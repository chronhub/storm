<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionDetail;
use Chronhub\Storm\Projector\Scheme\EventCounter;

use function in_array;

trait InteractWithPersistentSubscription
{
    public function update(): void
    {
        $this->repository->updateLock();
    }

    public function freed(): void
    {
        $this->repository->release();

        $this->setStatus(ProjectionStatus::IDLE);
    }

    public function close(): void
    {
        $idleStatus = ProjectionStatus::IDLE;

        $this->repository->stop($this->getProjectionDetail(), $idleStatus);

        $this->setStatus($idleStatus);

        $this->sprint()->stop();
    }

    public function restart(): void
    {
        $this->sprint()->continue();

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->startAgain($runningStatus);

        $this->setStatus($runningStatus);
    }

    public function disclose(): ProjectionStatus
    {
        return $this->repository->loadStatus();
    }

    public function synchronise(): void
    {
        $projectionDetail = $this->repository->loadDetail();

        $this->streamManager()->merge($projectionDetail->streamPositions);

        $state = $projectionDetail->state;

        if ($state !== []) {
            $this->state()->put($state);
        }
    }

    public function persistWhenCounterIsReached(): void
    {
        if ($this->eventCounter->isReached()) {
            $this->store();

            $this->eventCounter->reset();

            $this->setStatus($this->disclose());

            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->currentStatus(), $keepProjectionRunning, true)) {
                $this->sprint()->stop();
            }
        }
    }

    public function getName(): string
    {
        return $this->repository->projectionName();
    }

    public function eventCounter(): EventCounter
    {
        return $this->eventCounter;
    }

    protected function mountProjection(): void
    {
        $this->sprint()->continue();

        if (! $this->repository->exists()) {
            $this->repository->create($this->currentStatus());
        }

        $status = ProjectionStatus::RUNNING;

        $this->repository->start($status);

        $this->setStatus($status);
    }

    protected function getProjectionDetail(): ProjectionDetail
    {
        $streamPositions = $this->streamManager()->jsonSerialize();

        return new ProjectionDetail($streamPositions, $this->state()->get());
    }
}
