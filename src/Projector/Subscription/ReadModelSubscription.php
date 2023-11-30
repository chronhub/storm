<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Scheme\EventCounter;

final readonly class ReadModelSubscription implements ReadModelSubscriptionInterface
{
    use InteractWithPersistentSubscription;
    use InteractWithSubscription;

    public function __construct(
        protected GenericSubscription $subscription,
        protected ProjectionRepositoryInterface $repository,
        protected EventCounter $eventCounter,
        protected Chronicler $chronicler,
        private ReadModel $readModel,
    ) {
    }

    public function compose(ContextReaderInterface $context, ProjectorScope $projectorScope, bool $keepRunning): void
    {
        if (! $context->queryFilter() instanceof ProjectionQueryFilter) {
            throw new InvalidArgumentException('Persistent subscription require a projection query filter');
        }

        $this->subscription->compose($context, $projectorScope, $keepRunning);
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->syncStreams();
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

        $this->sprint()->stop();

        $this->resetProjection();
    }
}
