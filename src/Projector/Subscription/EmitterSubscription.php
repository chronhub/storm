<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Stream\StreamName;

final class EmitterSubscription implements EmitterSubscriptionInterface
{
    use InteractWithPersistentSubscription;

    private bool $isStreamFixed = false;

    public function __construct(
        protected readonly GenericSubscription $subscription,
        protected readonly ProjectionRepositoryInterface $repository,
        protected readonly EventCounter $eventCounter,
        protected readonly Chronicler $chronicler,
    ) {
    }

    public function rise(): void
    {
        $this->mountProjection();

        $this->discoverStreams();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionDetail());
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete();

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->sprint()->stop();

        $this->resetProjection();
    }

    public function revise(): void
    {
        $this->resetProjection();

        $this->repository->reset($this->getProjectionDetail(), $this->currentStatus());

        $this->deleteStream();
    }

    public function isStreamFixed(): bool
    {
        return $this->isStreamFixed;
    }

    public function fixeStream(): void
    {
        $this->isStreamFixed = true;
    }

    public function unfixStream(): void
    {
        $this->isStreamFixed = false;
    }

    private function deleteStream(): void
    {
        try {
            $this->chronicler->delete(new StreamName($this->projectionName()));
        } catch (StreamNotFound) {
            // fail silently
        }

        $this->unfixStream();
    }
}
