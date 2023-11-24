<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
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

        $this->streamManager()->syncStreams($projectionDetail->streamPositions);

        $state = $projectionDetail->state;

        if ($state !== []) {
            $this->subscription->state()->put($state);
        }
    }

    public function persistWhenCounterIsReached(): void
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

        $this->repository->stop($this->persistProjectionDetail(), $idleStatus);

        $this->subscription->setStatus($idleStatus);

        $this->sprint()->stop();
    }

    public function restart(): void
    {
        $this->subscription->sprint()->continue();

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->startAgain($runningStatus);

        $this->subscription->setStatus($runningStatus);
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

    protected function composeWithPersistence(ContextReaderInterface $context, ProjectorScope $projectionScope, bool $keepRunning): void
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

        $status = ProjectionStatus::RUNNING;

        $this->repository->start($status);

        $this->subscription->setStatus($status);
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

        return new ProjectionDetail($streamPositions, $this->state()->get());
    }
}
