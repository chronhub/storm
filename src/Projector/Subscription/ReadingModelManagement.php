<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelManagement;
use Chronhub\Storm\Projector\Subscription\Notification\EventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\ExpectStatus;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;

final readonly class ReadingModelManagement implements ReadModelManagement
{
    use InteractWithManagement;

    public function __construct(
        protected HookHub $hub,
        protected ProjectionRepository $repository,
        private ReadModel $readModel
    ) {
        HookHandler::subscribe($hub, $this);
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

        $this->repository->reset($this->getProjectionResult(), $this->hub->expect(ExpectStatus::class));

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
