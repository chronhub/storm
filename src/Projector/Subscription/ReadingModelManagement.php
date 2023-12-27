<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelManagement;
use Chronhub\Storm\Projector\Subscription\Notification\BatchStreamsReset;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointReset;
use Chronhub\Storm\Projector\Subscription\Notification\GetStatus;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Notification\StreamsDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateResetAgain;

final readonly class ReadingModelManagement implements ReadModelManagement
{
    use InteractWithManagement;

    public function __construct(
        protected HookHub $task,
        protected ProjectionRepository $repository,
        private ReadModel $readModel
    ) {
        EventManagement::subscribe($task, $this);
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->task->listen(StreamsDiscovered::class);

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionResult());

        $this->readModel->persist();

        $this->task->listen(BatchStreamsReset::class);
    }

    public function revise(): void
    {
        $this->task->listen(CheckpointReset::class);
        $this->task->listen(UserStateResetAgain::class);

        $this->repository->reset($this->getProjectionResult(), $this->task->listen(GetStatus::class));

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->task->listen(SprintStopped::class);
        $this->task->listen(CheckpointReset::class);
        $this->task->listen(UserStateResetAgain::class);
    }

    public function getReadModel(): ReadModel
    {
        return $this->readModel;
    }
}
