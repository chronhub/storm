<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionDetail;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamManager;

use function in_array;

abstract class AbstractPersistentSubscription extends AbstractSubscription implements PersistentSubscriptionInterface
{
    public function __construct(
        ProjectionOption $option,
        StreamManager $streamManager,
        SystemClock $clock,
        protected ProjectionRepositoryInterface $repository,
        protected EventCounter $eventCounter,

    ) {
        parent::__construct($option, $streamManager, $clock);
    }

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

        $this->streamManager->discoverStreams($projectionDetail->streamPositions);

        $state = $projectionDetail->state;

        if ($state !== []) {
            $this->state->put($state);
        }

        $this->streamManager->mergeGaps($projectionDetail->streamGaps);
    }

    public function renew(): void
    {
        $this->repository->attemptUpdateLockAndStreamPositions($this->streamManager->jsonSerialize());
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
        $this->streamManager()->watchStreams($this->context()->queries());

        $this->refreshDetail();
    }

    protected function resetProjection(): void
    {
        $this->streamManager()->resets();

        $this->initializeAgain();
    }

    protected function getProjectionDetail(): ProjectionDetail
    {
        return new ProjectionDetail(
            $this->streamManager->jsonSerialize(),
            $this->state->get(),
            $this->streamManager->confirmedGaps(),
        );
    }
}
