<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionDetail;
use Chronhub\Storm\Projector\Scheme\EventCounter;

use function in_array;

trait InteractWithPersistentSubscription
{
    use InteractWithSubscription {
        compose as protected composeWithPersistence;
    }

    public function synchronise(): void
    {
        $projectionDetail = $this->repository->loadDetail();

        $this->streamManager()->syncStreams(
            $projectionDetail->streamPositions,
            $projectionDetail->streamGaps
        );

        $state = $projectionDetail->state;

        if ($state !== []) {
            $this->subscription->state()->put($state);
        }
    }

    public function renew(): void
    {
        $currentTime = $this->clock()->now();

        if ($this->repository->canUpdate($currentTime)) {
            $this->repository->update($this->persistProjectionDetail(), $currentTime);
        }
    }

    public function freed(): void
    {
        $this->repository->release();

        $this->subscription->setStatus(ProjectionStatus::IDLE);
    }

    public function close(): void
    {
        $this->repository->stop($this->persistProjectionDetail());

        $this->subscription->setStatus(ProjectionStatus::IDLE);

        $this->sprint()->stop();
    }

    public function restart(): void
    {
        $this->subscription->sprint()->continue();

        $this->repository->startAgain();

        $this->subscription->setStatus(ProjectionStatus::RUNNING);
    }

    public function disclose(): ProjectionStatus
    {
        return $this->repository->loadStatus();
    }

    public function persistWhenThresholdIsReached(): void
    {
        if ($this->eventCounter->isReached()) {
            $this->store();

            $this->eventCounter()->reset();

            $this->subscription->setStatus($this->disclose());

            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->currentStatus(), $keepProjectionRunning, true)) {
                $this->sprint()->stop();
            }
        }
    }

    public function projectionName(): string
    {
        return $this->repository->projectionName();
    }

    public function eventCounter(): EventCounter
    {
        return $this->eventCounter;
    }

    protected function composeWithPersistence(ContextInterface $context, ProjectorScope $projectionScope, bool $keepRunning): void
    {
        if (! $context->queryFilter() instanceof ProjectionQueryFilter) {
            throw new InvalidArgumentException('Persistent subscription require a projection query filter');
        }

        $this->compose($context, $projectionScope, $keepRunning);
    }

    protected function mountProjection(): void
    {
        $this->sprint()->continue();

        if (! $this->repository->exists()) {
            $this->repository->create($this->subscription->currentStatus());
        }

        $this->repository->start();

        $this->subscription->setStatus(ProjectionStatus::RUNNING);
    }

    protected function syncStreams(): void
    {
        $this->streamManager()->watchStreams($this->context()->queries());

        $this->synchronise();
    }

    protected function resetProjection(): void
    {
        $this->streamManager()->resets();

        $this->initializeAgain();
    }

    protected function persistProjectionDetail(): ProjectionDetail
    {
        $streamPositions = $this->streamManager()->jsonSerialize();

        return new ProjectionDetail(
            $streamPositions['positions'],
            $this->state()->get(),
            $streamPositions['gaps'],
        );
    }
}
