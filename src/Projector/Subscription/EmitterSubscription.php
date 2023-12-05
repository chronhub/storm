<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Stream\StreamName;

final class EmitterSubscription implements EmitterSubscriptionInterface
{
    use InteractWithPersistentSubscription;
    use InteractWithSubscription {
        start as protected startDefault;
    }

    private bool $isStreamFixed = false;

    public function __construct(
        protected readonly GenericSubscription $subscription,
        protected ProjectionRepositoryInterface $repository,
        protected EventCounter $eventCounter,
    ) {
    }

    public function start(bool $keepRunning): void
    {
        if (! $this->context()->queryFilter() instanceof ProjectionQueryFilter) {
            throw new RuntimeException('Emitter subscription requires a projection query filter');
        }

        $this->startDefault($keepRunning);
    }

    public function rise(): void
    {
        $this->mountProjection();

        $this->streamManager()->discover($this->context()->queries());

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionDetail(), null);
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->sprint()->stop();

        $this->streamManager()->resets();

        $this->initializeAgain();
    }

    public function revise(): void
    {
        $this->streamManager()->resets();

        $this->initializeAgain();

        $this->repository->reset($this->getProjectionDetail(), $this->currentStatus());

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
            $this->chronicler()->delete(new StreamName($this->getName()));
        } catch (StreamNotFound) {
            // fail silently
        }

        $this->unsetEmitted();
    }
}
