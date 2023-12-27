<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelManagement;
use Chronhub\Storm\Projector\Subscription\Notification\BatchReset;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointReset;
use Chronhub\Storm\Projector\Subscription\Notification\GetStatus;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Notification\StreamsDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateResetAgain;

final readonly class ReadingModelManagement implements ReadModelManagement
{
    use InteractWithManagement;

    public function __construct(
        protected HookHub $hub,
        protected ProjectionRepository $repository,
        private ReadModel $readModel
    ) {
        EventManagement::subscribe($hub, $this);
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->hub->interact(StreamsDiscovered::class);

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionResult());

        $this->readModel->persist();

        $this->hub->interact(BatchReset::class);
    }

    public function revise(): void
    {
        $this->hub->interact(CheckpointReset::class);
        $this->hub->interact(UserStateResetAgain::class);

        $this->repository->reset($this->getProjectionResult(), $this->hub->interact(GetStatus::class));

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->hub->interact(SprintStopped::class);
        $this->hub->interact(CheckpointReset::class);
        $this->hub->interact(UserStateResetAgain::class);
    }

    public function getReadModel(): ReadModel
    {
        return $this->readModel;
    }
}
