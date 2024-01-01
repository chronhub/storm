<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Factory;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\ChroniclerDecorator;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\CheckpointRecognition;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\EmittedStreamCache;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriber;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\QuerySubscriber;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;
use Chronhub\Storm\Contracts\Projector\SubscriptionFactory;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Options\ProjectionOptionResolver;
use Chronhub\Storm\Projector\Repository\EventDispatcherRepository;
use Chronhub\Storm\Projector\Repository\LockManager;
use Chronhub\Storm\Projector\Scope\EmitterAccess;
use Chronhub\Storm\Projector\Scope\QueryAccess;
use Chronhub\Storm\Projector\Scope\ReadModelAccess;
use Chronhub\Storm\Projector\Stream\CheckpointCollection;
use Chronhub\Storm\Projector\Stream\CheckpointInMemory;
use Chronhub\Storm\Projector\Stream\CheckpointManager;
use Chronhub\Storm\Projector\Stream\EmittedStream;
use Chronhub\Storm\Projector\Stream\GapDetector;
use Chronhub\Storm\Projector\Stream\InMemoryEmittedStreams;
use Chronhub\Storm\Projector\Subscription\EmitterSubscription;
use Chronhub\Storm\Projector\Subscription\EmittingManagement;
use Chronhub\Storm\Projector\Subscription\HookHandler;
use Chronhub\Storm\Projector\Subscription\HubManager;
use Chronhub\Storm\Projector\Subscription\ListenerHandler;
use Chronhub\Storm\Projector\Subscription\QueryingManagement;
use Chronhub\Storm\Projector\Subscription\QuerySubscription;
use Chronhub\Storm\Projector\Subscription\ReadingModelManagement;
use Chronhub\Storm\Projector\Subscription\ReadModelSubscription;
use Chronhub\Storm\Projector\Subscription\SubscriptionManager;
use Chronhub\Storm\Projector\Support\Token\ConsumeWithSleepToken;
use Chronhub\Storm\Projector\Workflow\DefaultContext;
use Chronhub\Storm\Projector\Workflow\Timer;
use Chronhub\Storm\Projector\Workflow\Watcher\AckedStreamWatcher;
use Chronhub\Storm\Projector\Workflow\Watcher\BatchCounterWatcher;
use Chronhub\Storm\Projector\Workflow\Watcher\BatchStreamWatcher;
use Chronhub\Storm\Projector\Workflow\Watcher\EventStreamWatcher;
use Chronhub\Storm\Projector\Workflow\Watcher\LoopWatcher;
use Chronhub\Storm\Projector\Workflow\Watcher\MasterEventCounterWatcher;
use Chronhub\Storm\Projector\Workflow\Watcher\SprintWatcher;
use Chronhub\Storm\Projector\Workflow\Watcher\StopWatcher;
use Chronhub\Storm\Projector\Workflow\Watcher\TimeWatcher;
use Chronhub\Storm\Projector\Workflow\Watcher\UserStateWatcher;
use Chronhub\Storm\Projector\Workflow\Watcher\WatcherManager;
use Illuminate\Contracts\Events\Dispatcher;

abstract class AbstractSubscriptionFactory implements SubscriptionFactory
{
    protected Chronicler $chronicler;

    public function __construct(
        Chronicler $chronicler,
        protected readonly ProjectionProvider $projectionProvider,
        protected readonly EventStreamProvider $eventStreamProvider,
        protected readonly SystemClock $clock,
        protected readonly JsonSerializer $jsonSerializer,
        protected readonly Dispatcher $dispatcher,
        protected readonly ?ProjectionQueryScope $queryScope = null,
        protected readonly ProjectionOption|array $options = [],
    ) {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        $this->chronicler = $chronicler;
    }

    public function createQuerySubscription(ProjectionOption $option): QuerySubscriber
    {
        $subscriptor = $this->createSubscriptor($option, false);
        $notification = new HubManager($subscriptor);
        ListenerHandler::listen($notification);

        $activities = new QueryActivityFactory($this->chronicler);
        $scope = new QueryAccess($notification, $this->clock);

        return new QuerySubscription($subscriptor, new QueryingManagement($notification), $activities, $scope);
    }

    public function createEmitterSubscription(string $streamName, ProjectionOption $option): EmitterSubscriber
    {
        $subscriptor = $this->createSubscriptor($option, true);
        $notification = new HubManager($subscriptor);
        ListenerHandler::listen($notification);

        $management = new EmittingManagement(
            $notification,
            $this->chronicler,
            $this->createProjectionRepository($streamName, $option),
            $this->createStreamCache($option),
            new EmittedStream(),
        );

        HookHandler::subscribe($notification, $management);

        $activities = new PersistentActivityFactory($this->chronicler);
        $scope = new EmitterAccess($notification, $this->clock);

        return new EmitterSubscription($subscriptor, $management, $activities, $scope);
    }

    public function createReadModelSubscription(string $streamName, ReadModel $readModel, ProjectionOption $option): ReadModelSubscriber
    {
        $subscriptor = $this->createSubscriptor($option, true);
        $notification = new HubManager($subscriptor);
        ListenerHandler::listen($notification);

        $repository = $this->createProjectionRepository($streamName, $option);

        $management = new ReadingModelManagement($notification, $repository, $readModel);
        HookHandler::subscribe($notification, $management);

        $activities = new PersistentActivityFactory($this->chronicler);
        $scope = new ReadModelAccess($notification, $readModel, $this->clock);

        return new ReadModelSubscription($subscriptor, $management, $activities, $scope);
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

    protected function createSubscriptor(ProjectionOption $option, bool $detectGap): Subscriptor
    {
        return new SubscriptionManager(
            $this->createCheckpointRecognition($option, $detectGap),
            $this->clock,
            $option,
            $this->createMonitorManager($option),
        );
    }

    protected function createLockManager(ProjectionOption $option): LockManager
    {
        return new LockManager($this->clock, $option->getTimeout(), $option->getLockout());
    }

    protected function createCheckpointRecognition(ProjectionOption $option, bool $detectGap): CheckpointRecognition
    {
        $checkpoints = new CheckpointCollection($this->clock);

        if ($detectGap) {
            return new CheckpointManager(
                $checkpoints,
                new GapDetector($option->getRetries())
            );
        }

        return new CheckpointInMemory($checkpoints);
    }

    protected function createStreamCache(ProjectionOption $option): EmittedStreamCache
    {
        return new InMemoryEmittedStreams($option->getCacheSize());
    }

    protected function createDispatcherRepository(ProjectionRepository $projectionRepository): EventDispatcherRepository
    {
        return new EventDispatcherRepository($projectionRepository, $this->dispatcher);
    }

    protected function createMonitorManager(ProjectionOption $option): WatcherManager
    {
        return new WatcherManager(
            new LoopWatcher(),
            new SprintWatcher(),
            new UserStateWatcher(),
            new EventStreamWatcher($this->eventStreamProvider),
            new BatchCounterWatcher($option->getBlockSize()),
            new AckedStreamWatcher(),
            $this->batchStreamWatcher($option),
            new TimeWatcher(new Timer($this->clock)),
            new StopWatcher(),
            new MasterEventCounterWatcher(),
        );
    }

    protected function batchStreamWatcher(ProjectionOption $option): BatchStreamWatcher
    {
        [$capacity, $rate] = $option->getSleep();

        $bucket = new ConsumeWithSleepToken($capacity, $rate);

        return new BatchStreamWatcher($bucket);
    }
}
