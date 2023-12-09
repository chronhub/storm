<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;

readonly class ReadModelManagement implements SubscriptionManagement
{
    use InteractWithManagement;

    public function __construct(
        protected ReadModelSubscription $subscription,
        protected ProjectionRepositoryInterface $repository
    ) {
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->subscription->readModel()->isInitialized()) {
            $this->subscription->readModel()->initialize();
        }

        $this->subscription->streamBinder->discover(
            $this->subscription->context->queries()
        );

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionDetail(), null);

        $this->subscription->readModel()->persist();
    }

    public function revise(): void
    {
        $this->subscription->streamBinder->resets();

        $this->subscription->initializeAgain();

        $this->repository->reset($this->getProjectionDetail(), $this->subscription->currentStatus());

        $this->subscription->readModel()->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->subscription->readModel()->down();
        }

        $this->subscription->sprint->stop();

        $this->subscription->streamBinder->resets();

        $this->subscription->initializeAgain();
    }
}
