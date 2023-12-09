<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Projector\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\HandleStreamGap;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\Activity\PersistOrUpdate;
use Chronhub\Storm\Projector\Activity\RefreshProjection;
use Chronhub\Storm\Projector\Activity\ResetEventCounter;
use Chronhub\Storm\Projector\Activity\RisePersistentProjection;
use Chronhub\Storm\Projector\Activity\RunUntil;
use Chronhub\Storm\Projector\Activity\StopWhenRunningOnce;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionDetail;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\EventProcessor;

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

        $this->manager->setStatus(ProjectionStatus::IDLE);
    }

    public function close(): void
    {
        $idleStatus = ProjectionStatus::IDLE;

        $this->repository->stop($this->getProjectionDetail(), $idleStatus);

        $this->manager->setStatus($idleStatus);

        $this->manager->sprint->stop();
    }

    public function restart(): void
    {
        $this->manager->sprint->continue();

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->startAgain($runningStatus);

        $this->manager->setStatus($runningStatus);
    }

    public function disclose(): ProjectionStatus
    {
        return $this->repository->loadStatus();
    }

    public function synchronise(): void
    {
        $projectionDetail = $this->repository->loadDetail();

        $this->manager->streamBinder->merge($projectionDetail->streamPositions);

        $state = $projectionDetail->state;

        if ($state !== []) {
            $this->manager->state()->put($state);
        }
    }

    public function persistWhenCounterIsReached(): void
    {
        if ($this->eventCounter->isReached()) {
            $this->store();

            $this->eventCounter->reset();

            $this->manager->setStatus($this->disclose());

            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->manager->currentStatus(), $keepProjectionRunning, true)) {
                $this->manager->sprint->stop();
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
        $this->manager->sprint->continue();

        if (! $this->repository->exists()) {
            $this->repository->create(
                $this->manager->currentStatus()
            );
        }

        $status = ProjectionStatus::RUNNING;

        $this->repository->start($status);

        $this->manager->setStatus($status);
    }

    protected function getProjectionDetail(): ProjectionDetail
    {
        $streamPositions = $this->manager->streamBinder->jsonSerialize();

        return new ProjectionDetail($streamPositions, $this->manager->state()->get());
    }

    protected function getActivities(): array
    {
        return [
            new RunUntil(),
            new RisePersistentProjection($this),
            new LoadStreams(),
            new HandleStreamEvent(
                new EventProcessor($this, $this->manager->context->reactors(), $this->getScope())
            ),
            new HandleStreamGap($this),
            new PersistOrUpdate($this),
            new ResetEventCounter($this->eventCounter),
            new DispatchSignal(),
            new RefreshProjection($this),
            new StopWhenRunningOnce($this),
        ];
    }
}
