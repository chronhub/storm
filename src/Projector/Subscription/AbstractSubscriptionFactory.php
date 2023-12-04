<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionOptionImmutable;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\QuerySubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\StreamCacheInterface;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Contracts\Projector\SubscriptionFactory;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Options\DefaultOption;
use Chronhub\Storm\Projector\Repository\EventDispatcherRepository;
use Chronhub\Storm\Projector\Repository\LockManager;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\EventStreamLoader;
use Chronhub\Storm\Projector\Scheme\StreamCache;
use Chronhub\Storm\Projector\Scheme\StreamManager;
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

    public function createQuerySubscription(ProjectionOption $option): QuerySubscriptionInterface
    {
        return new QuerySubscription(
            $this->createGenericSubscription($option),
        );
    }

    public function createEmitterSubscription(string $streamName, ProjectionOption $option): EmitterSubscriptionInterface
    {
        return new EmitterSubscription(
            $this->createGenericSubscription($option),
            $this->createSubscriptionManagement($streamName, $option),
            $this->createEventCounter($option),
        );
    }

    public function createReadModelSubscription(string $streamName, ReadModel $readModel, ProjectionOption $option): ReadModelSubscriptionInterface
    {
        return new ReadModelSubscription(
            $this->createGenericSubscription($option),
            $this->createSubscriptionManagement($streamName, $option),
            $this->createEventCounter($option),
            $readModel,
        );
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

    public function createStreamCache(ProjectionOption $option): StreamCacheInterface
    {
        return new StreamCache($option->getCacheSize());
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

    abstract protected function createSubscriptionManagement(string $streamName, ProjectionOption $options): ProjectionRepositoryInterface;

    protected function createGenericSubscription(ProjectionOption $option): GenericSubscription
    {
        return new GenericSubscription(
            $this->createContextBuilder(),
            $this->createStreamManager($option),
            $this->clock,
            $option,
            $this->chronicler,
        );
    }

    protected function createContextBuilder(): ContextReaderInterface
    {
        return new Context();
    }

    protected function createLockManager(ProjectionOption $option): LockManager
    {
        return new LockManager($this->clock, $option->getTimeout(), $option->getLockout());
    }

    protected function createStreamManager(ProjectionOption $options): StreamManagerInterface
    {
        return new StreamManager(
            new EventStreamLoader($this->eventStreamProvider),
            $this->clock,
            $options->getRetries(),
            $options->getDetectionWindows()
        );
    }

    protected function createEventCounter(ProjectionOption $options): EventCounter
    {
        return new EventCounter($options->getBlockSize());
    }

    protected function createDispatcherRepository(ProjectionRepositoryInterface $projectionRepository): EventDispatcherRepository
    {
        return new EventDispatcherRepository($projectionRepository, $this->dispatcher);
    }
}
