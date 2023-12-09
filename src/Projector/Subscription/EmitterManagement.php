<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use Chronhub\Storm\Stream\StreamName;

final readonly class EmitterManagement implements SubscriptionManagement
{
    use InteractWithManagement;

    public function __construct(
        protected EmitterSubscription $subscription,
        protected ProjectionRepositoryInterface $repository,
    ) {
    }

    public function rise(): void
    {
        $this->mountProjection();

        $this->subscription->streamBinder->discover($this->subscription->context->queries());

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionDetail(), null);
    }

    public function revise(): void
    {
        $this->subscription->streamBinder->resets();

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

        $this->subscription->streamBinder->resets();

        $this->subscription->initializeAgain();
    }

    private function deleteStream(): void
    {
        try {
            $this->subscription->chronicler->delete(
                new StreamName($this->repository->projectionName())
            );
        } catch (StreamNotFound) {
            // ignore
        }
    }
}
