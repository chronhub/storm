<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;
use Chronhub\Storm\Projector\Scheme\EventCounter;

final class ReadModelSubscription implements ReadModelSubscriptionInterface
{
    use InteractWithPersistentSubscription;

    public function __construct(
        protected readonly GenericSubscription $subscription,
        protected readonly ProjectionRepositoryInterface $repository,
        protected readonly EventCounter $eventCounter,
        protected readonly Chronicler $chronicler,
        private readonly ReadModel $readModel,
    ) {
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->discoverStreams();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionDetail());

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->resetProjection();

        $this->repository->reset($this->getProjectionDetail(), $this->currentStatus());

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete(); // todo propagate $withEmittedEvents as info for dispatcher

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->sprint()->stop();

        $this->resetProjection();
    }
}
