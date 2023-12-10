<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriber;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionOptionImmutable;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\QuerySubscriber;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;
use Chronhub\Storm\Contracts\Projector\StreamCache;
use Chronhub\Storm\Contracts\Projector\StreamManager;
use Chronhub\Storm\Contracts\Projector\SubscriptionFactory;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Options\DefaultOption;
use Chronhub\Storm\Projector\Repository\EventDispatcherRepository;
use Chronhub\Storm\Projector\Repository\LockManager;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\EmittedStream;
use Chronhub\Storm\Projector\Scheme\EmittedStreamCache;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\EventStreamLoader;
use Chronhub\Storm\Projector\Scheme\StreamBinder;
use Chronhub\Storm\Projector\Scheme\StreamGap;
use Illuminate\Contracts\Events\Dispatcher;

use function array_merge;
use function get_class;

abstract class AbstractSubscriptionFactory implements SubscriptionFactory
{
    public function __construct(
        protected readonly Chronicler $chronicler,
        protected readonly ProjectionProvider $projectionProvider,
        protected readonly EventStreamProvider $eventStreamProvider,
        protected readonly SystemClock $clock,
        protected readonly JsonSerializer $jsonSerializer,
        protected readonly Dispatcher $dispatcher,
        protected readonly ?ProjectionQueryScope $queryScope = null,
        protected readonly ProjectionOption|array $options = [],
    ) {
    }

    // todo provide persistent subscription withNoGapDetection
    //  or does a flag in the option is enough ?
    //  by now, we use a method in subscription to check if gap detection is enabled
    //  fine till we persists gaps also in the stream

    public function createQuerySubscription(ProjectionOption $option): QuerySubscriber
    {
        $subscription = $this->createSubscription($option);

        return new QuerySubscription($subscription, new QueryingManagement($subscription));
    }

    public function createEmitterSubscription(string $streamName, ProjectionOption $option): EmitterSubscriber
    {
        $subscription = $this->createSubscriptionWithGapDetection($option);
        $subscription->setEventCounter($this->createEventCounter($option));

        $management = new EmittingManagement(
            $subscription,
            $this->createProjectionRepository($streamName, $option),
            $this->createStreamCache($option),
            new EmittedStream()
        );

        return new EmitterSubscription($subscription, $management);
    }

    public function createReadModelSubscription(string $streamName, ReadModel $readModel, ProjectionOption $option): ReadModelSubscriber
    {
        $subscription = $this->createSubscriptionWithGapDetection($option);
        $subscription->setEventCounter($this->createEventCounter($option));

        $management = new ReadingModelManagement(
            $subscription,
            $this->createProjectionRepository($streamName, $option),
            $readModel
        );

        return new ReadModelSubscription($subscription, $management);
    }

    public function createOption(array $options = []): ProjectionOption
    {
        if ($options !== []) {
            if ($this->options instanceof ProjectionOption && ! $this->options instanceof ProjectionOptionImmutable) {
                $optionClass = get_class($this->options);

                return new $optionClass(...array_merge($this->options->jsonSerialize(), $options));
            }

            return new DefaultOption(...$options);
        }

        if ($this->options instanceof ProjectionOption) {
            return $this->options;
        }

        return new DefaultOption(...$this->options);
    }

    public function createContextBuilder(): ContextReaderInterface
    {
        return new Context();
    }

    public function getProjectionProvider(): ProjectionProvider
    {
        return $this->projectionProvider;
    }

    public function getSerializer(): JsonSerializer
    {
        return $this->jsonSerializer;
    }

    public function getQueryScope(): ?ProjectionQueryScope
    {
        return $this->queryScope;
    }

    protected function createSubscription(ProjectionOption $option): Subscription
    {
        return new Subscription(
            $this->createStreamManager(),
            $this->clock,
            $option,
            $this->chronicler,
        );
    }

    protected function createSubscriptionWithGapDetection(ProjectionOption $option): Subscription
    {
        return new Subscription(
            $this->createStreamGapManager($option),
            $this->clock,
            $option,
            $this->chronicler,
        );
    }

    abstract protected function createProjectionRepository(string $streamName, ProjectionOption $options): ProjectionRepository;

    protected function createLockManager(ProjectionOption $option): LockManager
    {
        return new LockManager($this->clock, $option->getTimeout(), $option->getLockout());
    }

    protected function createStreamManager(): StreamManager
    {
        return new StreamBinder(new EventStreamLoader($this->eventStreamProvider));
    }

    protected function createStreamGapManager(ProjectionOption $options): StreamManager
    {
        return new StreamGap(
            $this->createStreamManager(),
            $this->clock,
            $options->getRetries(),
            $options->getDetectionWindows()
        );
    }

    protected function createEventCounter(ProjectionOption $options): EventCounter
    {
        return new EventCounter($options->getBlockSize());
    }

    protected function createStreamCache(ProjectionOption $option): StreamCache
    {
        return new EmittedStreamCache($option->getCacheSize());
    }

    protected function createDispatcherRepository(ProjectionRepository $projectionRepository): EventDispatcherRepository
    {
        return new EventDispatcherRepository($projectionRepository, $this->dispatcher);
    }
}
