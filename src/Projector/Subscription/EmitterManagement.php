<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use Chronhub\Storm\Stream\StreamName;

final readonly class EmitterManagement implements SubscriptionManagement
{
    use InteractWithManagement;

    public function __construct(
        protected Subscription $subscription,
        protected ProjectionRepository $repository,
    ) {
    }

    public function rise(): void
    {
        $this->mountProjection();

        $this->subscription->discoverStreams();

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionDetail(), null);
    }

    public function revise(): void
    {
        $this->subscription->streamManager->resets();

        $this->subscription->initializeAgain();

        $this->repository->reset($this->getProjectionDetail(), $this->subscription->currentStatus());

        $this->deleteStream();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->subscription->sprint->stop();

        $this->subscription->streamManager->resets();

        $this->subscription->initializeAgain();
    }

    private function deleteStream(): void
    {
        try {
            $streamName = new StreamName($this->repository->projectionName());

            $this->subscription->chronicler->delete($streamName);
        } catch (StreamNotFound) {
            // ignore
        }
    }
}
