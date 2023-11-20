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

    public function refreshDetail(): void
    {
        $projectionDetail = $this->repository->loadDetail();

        $this->streamManager()->discoverStreams($projectionDetail->streamPositions);

        $state = $projectionDetail->state;

        if ($state !== []) {
            $this->subscription->state()->put($state);
        }

        $this->streamManager()->mergeGaps($projectionDetail->streamGaps);
    }

    public function renew(): void
    {
        $this->repository->attemptUpdateStreamPositions(
            $this->subscription->streamManager()->jsonSerialize()
        );
    }

    public function freed(): void
    {
        $this->repository->releaseLock();

        $this->subscription->setStatus(ProjectionStatus::IDLE);
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

    public function close(): void
    {
        $this->repository->stop($this->getProjectionDetail());

        $this->subscription->setStatus(ProjectionStatus::IDLE);

        $this->sprint()->stop();
    }

    public function restart(): void
    {
        $this->subscription->sprint()->continue();

        $this->repository->startAgain();

        $this->subscription->setStatus(ProjectionStatus::RUNNING);
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

        $this->repository->acquireLock();

        $this->subscription->setStatus(ProjectionStatus::RUNNING);
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
            $this->streamManager()->jsonSerialize(),
            $this->state()->get(),
            $this->streamManager()->confirmedGaps(),
        );
    }
}
