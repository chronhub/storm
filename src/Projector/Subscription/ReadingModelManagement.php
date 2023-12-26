<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelManagement;

final readonly class ReadingModelManagement implements ReadModelManagement
{
    use InteractWithManagement;

    public function __construct(
        protected Notification $notification,
        protected ProjectionRepository $repository,
        private ReadModel $readModel
    ) {
        EventManagement::subscribe($notification, $this);
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

        $this->notification->onBatchStreamsReset();
    }

    public function revise(): void
    {
        $this->notification->onCheckpointReset();
        $this->notification->onUserStateReset();

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
        $this->notification->onUserStateReset();
    }

    public function getReadModel(): ReadModel
    {
        return $this->readModel;
    }
}
