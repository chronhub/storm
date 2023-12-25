<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Factory;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriber;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
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
use Chronhub\Storm\Projector\Options\ProjectionOptionResolver;
use Chronhub\Storm\Projector\Repository\EventDispatcherRepository;
use Chronhub\Storm\Projector\Repository\LockManager;
use Chronhub\Storm\Projector\Stream\CheckpointCollection;
use Chronhub\Storm\Projector\Stream\CheckpointManager;
use Chronhub\Storm\Projector\Stream\EmittedStream;
use Chronhub\Storm\Projector\Stream\EventStreamDiscovery;
use Chronhub\Storm\Projector\Stream\GapDetector;
use Chronhub\Storm\Projector\Stream\InMemoryStreams;
use Chronhub\Storm\Projector\Subscription\EmitterSubscription;
use Chronhub\Storm\Projector\Subscription\EmittingManagement;
use Chronhub\Storm\Projector\Subscription\QueryingManagement;
use Chronhub\Storm\Projector\Subscription\QuerySubscription;
use Chronhub\Storm\Projector\Subscription\ReadingModelManagement;
use Chronhub\Storm\Projector\Subscription\ReadModelSubscription;
use Chronhub\Storm\Projector\Subscription\SubscriptionManager;
use Chronhub\Storm\Projector\Support\EventCounter;
use Chronhub\Storm\Projector\Support\Loop;
use Chronhub\Storm\Projector\Workflow\DefaultContext;
use Illuminate\Contracts\Events\Dispatcher;

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

    public function createQuerySubscription(ProjectionOption $option): QuerySubscriber
    {
        $subscriptor = $this->createSusbcriptor($option, false);

        return new QuerySubscription($subscriptor, new QueryingManagement($subscriptor));
    }

    public function createEmitterSubscription(string $streamName, ProjectionOption $option): EmitterSubscriber
    {
        $subscriptor = $this->createSusbcriptor($option, true);

        $management = new EmittingManagement(
            $subscriptor,
            $this->chronicler,
            $this->createProjectionRepository($streamName, $option),
            $this->createStreamCache($option),
            new EmittedStream()
        );

        return new EmitterSubscription($subscriptor, $management);
    }

    public function createReadModelSubscription(string $streamName, ReadModel $readModel, ProjectionOption $option): ReadModelSubscriber
    {
        $subscriptor = $this->createSusbcriptor($option, true);

        $management = new ReadingModelManagement(
            $subscriptor,
            $this->createProjectionRepository($streamName, $option),
            $readModel
        );

        return new ReadModelSubscription($subscriptor, $management);
    }

    public function createOption(array $options = []): ProjectionOption
    {
        $resolver = new ProjectionOptionResolver($this->options);

        return $resolver($options);
    }

    public function createContextBuilder(): ContextReader
    {
        return new DefaultContext();
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

    abstract protected function useEvents(bool $useEvents): void;

    abstract protected function createProjectionRepository(string $streamName, ProjectionOption $options): ProjectionRepository;

    protected function createSusbcriptor(ProjectionOption $option, bool $isPersistent): SubscriptionManager
    {
        $activities = $isPersistent ? new PersistentActivityFactory() : new QueryActivityFactory();

        return new SubscriptionManager(
            $this->createEventStreamDiscovery(),
            $this->createStreamManager($option), // todo query does not handle gap
            $this->clock,
            $option,
            new Loop(),
            $activities,
            $this->chronicler,
        );
    }

    protected function createEventStreamDiscovery(): EventStreamDiscovery
    {
        return new EventStreamDiscovery($this->eventStreamProvider);
    }

    protected function createLockManager(ProjectionOption $option): LockManager
    {
        return new LockManager($this->clock, $option->getTimeout(), $option->getLockout());
    }

    protected function createStreamManager(ProjectionOption $option): StreamManager
    {
        return new CheckpointManager(
            new CheckpointCollection($this->clock),
            new GapDetector($option->getRetries(), $option->getDetectionWindows())
        );
    }

    protected function createEventCounter(ProjectionOption $options): EventCounter
    {
        return new EventCounter($options->getBlockSize());
    }

    protected function createStreamCache(ProjectionOption $option): StreamCache
    {
        return new InMemoryStreams($option->getCacheSize());
    }

    protected function createDispatcherRepository(ProjectionRepository $projectionRepository): EventDispatcherRepository
    {
        return new EventDispatcherRepository($projectionRepository, $this->dispatcher);
    }
}
