<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Stream\StreamName;

final class EmitterSubscription implements EmitterSubscriptionInterface
{
    use InteractWithPersistentSubscription;
    use InteractWithSubscription;

    private bool $isStreamFixed = false;

    public function __construct(
        protected readonly GenericSubscription $subscription,
        protected readonly ProjectionRepositoryInterface $repository,
        protected readonly EventCounter $eventCounter,
        protected readonly Chronicler $chronicler,
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

        $this->syncStreams();
    }

    public function store(): void
    {
        $this->repository->persist($this->persistProjectionDetail(), null);
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->sprint()->stop();

        $this->resetProjection();
    }

    public function revise(): void
    {
        $this->resetProjection();

        $this->repository->reset($this->persistProjectionDetail(), $this->currentStatus());

        $this->deleteStream();
    }

    public function wasEmitted(): bool
    {
        return $this->isStreamFixed;
    }

    public function eventEmitted(): void
    {
        $this->isStreamFixed = true;
    }

    public function unsetEmitted(): void
    {
        $this->isStreamFixed = false;
    }

    private function deleteStream(): void
    {
        try {
            $this->chronicler->delete(new StreamName($this->getName()));
        } catch (StreamNotFound) {
            // fail silently
        }

        $this->unsetEmitted();
    }
}
