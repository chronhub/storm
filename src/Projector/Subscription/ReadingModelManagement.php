<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelManagement;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRised;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStored;

final readonly class ReadingModelManagement implements ReadModelManagement
{
    use InteractWithManagement;

    public function __construct(
        protected Notification $notification,
        protected ProjectionRepository $repository,
        private ReadModel $readModel
    ) {
        $this->notification->listen(ProjectionRised::class, function (): void {
            $this->rise();
        });

        $this->notification->listen(ProjectionLockUpdated::class, function (): void {
            $this->tryUpdateLock();
        });

        $this->notification->listen(ProjectionStored::class, function (): void {
            $this->store();
        });
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->notification->onStreamsDiscovered();

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionResult());

        $this->readModel->persist();

        $this->notification->onResetBatchStreams();
    }

    public function revise(): void
    {
        $this->notification->onCheckpointReset();
        $this->notification->onOriginalUserStateReset();

        $this->repository->reset($this->getProjectionResult(), $this->notification->observeStatus());
        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->notification->onProjectionStopped();
        $this->notification->onCheckpointReset();
        $this->notification->onOriginalUserStateReset();
    }

    public function getReadModel(): ReadModel
    {
        return $this->readModel;
    }
}
