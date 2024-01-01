<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelManagement;
use Chronhub\Storm\Projector\Subscription\Sprint\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Status\CurrentStatus;
use Chronhub\Storm\Projector\Subscription\Stream\EventStreamDiscovered;

final readonly class ReadingModelManagement implements ReadModelManagement
{
    use InteractWithManagement;

    public function __construct(
        protected NotificationHub $hub,
        protected ProjectionRepository $repository,
        private ReadModel $readModel
    ) {
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->hub->notify(EventStreamDiscovered::class);

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionResult());

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->resetState();

        $this->repository->reset($this->getProjectionResult(), $this->hub->expect(CurrentStatus::class));

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->hub->notify(SprintStopped::class);

        $this->resetState();
    }

    public function getReadModel(): ReadModel
    {
        return $this->readModel;
    }
}
