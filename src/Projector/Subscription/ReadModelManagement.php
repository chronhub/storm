<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionManagement;

final readonly class ReadModelManagement implements ReadModelSubscriptionManagement
{
    use InteractWithManagement;

    public function __construct(
        protected Subscription $subscription,
        protected ProjectionRepository $repository,
        private ReadModel $readModel
    ) {
    }

    public function getReadModel(): ReadModel
    {
        return $this->readModel;
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->subscription->discoverStreams();

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionDetail(), null);

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->subscription->streamManager->resets();

        $this->subscription->initializeAgain();

        $this->repository->reset($this->getProjectionDetail(), $this->subscription->currentStatus());

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->subscription->sprint->stop();

        $this->subscription->streamManager->resets();

        $this->subscription->initializeAgain();
    }
}
