<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\Sprint;

final class ReadModelSubscription implements ReadModelSubscriptionInterface
{
    use InteractWithPersistentSubscription;
    use InteractWithSubscription;

    protected ProjectionStateInterface $state;

    protected Sprint $sprint;

    protected Chronicler $chronicler;

    public function __construct(
        protected ProjectionRepositoryInterface $repository,
        protected StreamManagerInterface $streamManager,
        protected ProjectionOption $option,
        protected SystemClock $clock,
        protected EventCounter $eventCounter,
        private readonly ReadModel $readModel,
        Chronicler $chronicler,
    ) {
        $this->chronicler = $this->resolveInnerMostChronicler($chronicler);
        $this->state = new ProjectionState();
        $this->sprint = new Sprint();
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->syncStreamsOnRise();
    }

    public function store(): void
    {
        $this->repository->persist($this->persistProjectionDetail(), null);

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->resetProjection();

        $this->repository->reset($this->persistProjectionDetail(), $this->currentStatus());

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->sprint->stop();

        $this->resetProjection();
    }
}
