<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Scheme\GapDetector;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Repository\LockManager;
use Chronhub\Storm\Contracts\Projector\ContextBuilder;
use Chronhub\Storm\Contracts\Projector\ProjectionStore;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Projector\Options\DefaultProjectionOption;
use Chronhub\Storm\Contracts\Projector\PersistentViewSubscription;
use Chronhub\Storm\Projector\Repository\StandaloneProjectionStore;
use Chronhub\Storm\Contracts\Projector\PersistentReadModelSubscription;
use function array_merge;

abstract readonly class SubscriptionFactory
{
    public function __construct(public Chronicler $chronicler,
                                public ProjectionProvider $projectionProvider,
                                public EventStreamProvider $eventStreamProvider,
                                public ProjectionQueryScope $queryScope,
                                public SystemClock $clock,
                                public MessageAlias $messageAlias,
                                public JsonSerializer $jsonSerializer,
                                public ProjectionOption|array $options = [])
    {
    }

    public function createLiveSubscription(array $options = []): Subscription
    {
        return new LiveSubscription(
            $this->createOption($options),
            $this->createStreamPosition(),
            $this->clock
        );
    }

    public function createPersistentSubscription(array $options = []): PersistentViewSubscription
    {
        $subscriptionOption = $this->createOption($options);
        $streamPosition = $this->createStreamPosition();

        return new BoundSubscription(
            $subscriptionOption,
            $this->createStreamPosition(),
            $this->createEventCounter($subscriptionOption),
            $this->createGapDetector($streamPosition, $subscriptionOption),
            $this->clock
        );
    }

    public function createReadModelSubscription(array $options = []): PersistentReadModelSubscription
    {
        $subscriptionOption = $this->createOption($options);
        $streamPosition = $this->createStreamPosition();

        return new ReadModelSubscription(
            $subscriptionOption,
            $streamPosition,
            $this->createEventCounter($subscriptionOption),
            $this->createGapDetector($streamPosition, $subscriptionOption),
            $this->clock
        );
    }

    public function createContextBuilder(): ContextBuilder
    {
        return new Context();
    }

    abstract public function createSubscriptionManagement(
        PersistentViewSubscription|PersistentReadModelSubscription $subscription,
        string $streamName,
        ?ReadModel $readModel): ProjectionRepository;

    protected function createStore(Subscription $subscription, string $streamName): ProjectionStore
    {
        return new StandaloneProjectionStore(
            $subscription,
            $this->projectionProvider,
            $this->createLock($subscription->option()),
            $this->jsonSerializer,
            $streamName
        );
    }

    protected function createLock(ProjectionOption $option): LockManager
    {
        return new LockManager($this->clock, $option->getTimeout(), $option->getLockout());
    }

    protected function createOption(array $options): ProjectionOption
    {
        if ($this->options instanceof ProjectionOption) {
            return $this->options;
        }

        return new DefaultProjectionOption(...array_merge($this->options, $options));
    }

    protected function createGapDetector(StreamPosition $streamPosition, ProjectionOption $options): GapDetector
    {
        return new GapDetector(
            $streamPosition,
            $this->clock,
            $options->getRetries(),
            $options->getDetectionWindows()
        );
    }

    protected function createStreamPosition(): StreamPosition
    {
        return new StreamPosition($this->eventStreamProvider);
    }

    protected function createEventCounter(ProjectionOption $options): EventCounter
    {
        return new EventCounter($options->getBlockSize());
    }
}
