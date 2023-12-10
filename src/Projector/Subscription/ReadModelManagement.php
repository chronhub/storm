<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;

final readonly class ReadModelManagement implements SubscriptionManagement
{
    use InteractWithManagement;

    public function __construct(
        protected Subscription $subscription,
        protected ProjectionRepository $repository
    ) {
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->subscription->readModel->isInitialized()) {
            $this->subscription->readModel->initialize();
        }

        $this->subscription->discoverStreams();

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionDetail(), null);

        $this->subscription->readModel->persist();
    }

    public function revise(): void
    {
        $this->subscription->streamManager->resets();

        $this->subscription->initializeAgain();

        $this->repository->reset($this->getProjectionDetail(), $this->subscription->currentStatus());

        $this->subscription->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->subscription->readModel->down();
        }

        $this->subscription->sprint->stop();

        $this->subscription->streamManager->resets();

        $this->subscription->initializeAgain();
    }
}
