<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionDetail;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamGapManager;

use function in_array;

abstract class AbstractPersistentSubscription extends AbstractSubscription implements PersistentSubscriptionInterface
{
    protected ProjectionRepositoryInterface $repository;

    protected EventCounter $eventCounter;

    protected StreamGapManager $gap;

    public function rise(): void
    {
        $this->mountProjection();

        $this->discoverStreams();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionDetail());
    }

    public function persistWhenThresholdIsReached(): void
    {
        if ($this->eventCounter->isReached()) {
            $this->store();

            $this->eventCounter()->reset();

            $this->setStatus($this->disclose());

            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->currentStatus(), $keepProjectionRunning, true)) {
                $this->sprint()->stop();
            }
        }
    }

    public function revise(): void
    {
        $this->resetProjection();

        $this->repository->reset($this->getProjectionDetail(), $this->status);
    }

    public function close(): void
    {
        $this->repository->stop($this->getProjectionDetail());

        $this->status = ProjectionStatus::IDLE;

        $this->sprint()->stop();
    }

    public function restart(): void
    {
        $this->sprint->continue();

        $this->repository->startAgain();

        $this->status = ProjectionStatus::RUNNING;
    }

    public function refreshDetail(): void
    {
        $projectionDetail = $this->repository->loadDetail();

        $this->streamPosition->discover($projectionDetail->streamPositions);

        $state = $projectionDetail->state;

        if ($state !== []) {
            $this->state->put($state);
        }

        $this->gap->mergeGaps($this->currentStreamName(), $projectionDetail->streamGaps);
    }

    public function renew(): void
    {
        $this->repository->updateLock($this->streamPosition->all());
    }

    public function freed(): void
    {
        $this->repository->releaseLock();

        $this->status = ProjectionStatus::IDLE;
    }

    public function disclose(): ProjectionStatus
    {
        return $this->repository->loadStatus();
    }

    public function projectionName(): string
    {
        return $this->repository->projectionName();
    }

    public function eventCounter(): EventCounter
    {
        return $this->eventCounter;
    }

    public function gap(): StreamGapManager
    {
        return $this->gap;
    }

    protected function mountProjection(): void
    {
        $this->sprint()->continue();

        if (! $this->repository->exists()) {
            $this->repository->create($this->status);
        }

        $this->repository->acquireLock();

        $this->status = ProjectionStatus::RUNNING;
    }

    protected function discoverStreams(): void
    {
        $this->streamPosition()->watch($this->context()->queries());

        $this->refreshDetail();
    }

    protected function resetProjection(): void
    {
        $this->streamPosition()->reset();

        $this->gap()->resetGaps();

        $this->initializeAgain();
    }

    protected function getProjectionDetail(): ProjectionDetail
    {
        return new ProjectionDetail(
            $this->streamPosition->all(),
            $this->state->get(),
            $this->gap->getConfirmedGaps($this->currentStreamName()),
        );
    }
}
