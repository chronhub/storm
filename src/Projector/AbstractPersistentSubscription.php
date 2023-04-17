<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamGapDetector;
use function is_array;

abstract class AbstractPersistentSubscription extends AbstractSubscription implements PersistentSubscriptionInterface
{
    protected ProjectionRepositoryInterface $repository;

    protected EventCounter $eventCounter;

    protected StreamGapDetector $gap;

    public function rise(): void
    {
        $this->mountProjection();

        $this->discoverStreams();
    }

    public function store(): void
    {
        $this->repository->persist($this->streamPosition->all(), $this->state->get());
    }

    public function revise(): void
    {
        $this->resetProjectionState();

        $this->repository->reset(
            $this->streamPosition->all(),
            $this->state->get(),
            $this->status
        );
    }

    public function close(): void
    {
        $this->repository->stop($this->streamPosition->all(), $this->state->get());

        $this->status = ProjectionStatus::IDLE;

        $this->sprint()->stop();
    }

    public function restart(): void
    {
        $this->sprint->continue();

        $this->repository->startAgain();

        $this->status = ProjectionStatus::RUNNING;
    }

    public function boundState(): void
    {
        [$streamPositions, $state] = $this->repository->loadState();

        $this->streamPosition->discover($streamPositions);

        if (is_array($state) && $state !== []) {
            $this->state->put($state);
        }
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

    public function gap(): StreamGapDetector
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

        $this->boundState();
    }

    protected function resetProjectionState(): void
    {
        $this->streamPosition()->reset();

        $this->initializeAgain();
    }
}
