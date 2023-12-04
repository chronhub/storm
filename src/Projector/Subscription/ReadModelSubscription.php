<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Scheme\EventCounter;

final class ReadModelSubscription implements ReadModelSubscriptionInterface
{
    use InteractWithPersistentSubscription;
    use InteractWithSubscription {
        compose as protected composeDefault;
    }

    public function __construct(
        protected readonly GenericSubscription $subscription,
        protected ProjectionRepositoryInterface $repository,
        protected EventCounter $eventCounter,
        private readonly ReadModel $readModel,
    ) {
    }

    public function compose(ProjectorScope $projectorScope, bool $keepRunning): void
    {
        if (! $this->context()->queryFilter() instanceof ProjectionQueryFilter) {
            throw new RuntimeException('Read model subscription requires a projection query filter');
        }

        $this->composeDefault($projectorScope, $keepRunning);
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->streamManager()->discover($this->context()->queries());

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionDetail(), null);

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->streamManager()->resets();

        $this->initializeAgain();

        $this->repository->reset($this->getProjectionDetail(), $this->currentStatus());

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->sprint()->stop();

        $this->streamManager()->resets();

        $this->initializeAgain();
    }
}
