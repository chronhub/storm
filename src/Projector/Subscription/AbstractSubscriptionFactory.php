<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Contracts\Projector\SubscriptionFactory;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Options\DefaultProjectionOption;
use Chronhub\Storm\Projector\Repository\EventDispatcherRepository;
use Chronhub\Storm\Projector\Repository\LockManager;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\EventStreamLoader;
use Chronhub\Storm\Projector\Scheme\StreamManager;
use Illuminate\Contracts\Events\Dispatcher;

use function array_merge;

abstract class AbstractSubscriptionFactory implements SubscriptionFactory
{
    public function __construct(
        public readonly Chronicler $chronicler,
        public readonly ProjectionProvider $projectionProvider,
        public readonly EventStreamProvider $eventStreamProvider,
        public readonly SystemClock $clock,
        public readonly JsonSerializer $jsonSerializer,
        public readonly Dispatcher $dispatcher,
        public readonly ?ProjectionQueryScope $queryScope = null,
        public readonly ProjectionOption|array $options = [],
    ) {
    }

    public function createQuerySubscription(array $options = []): Subscription
    {
        $subscription = $this->createGenericSubscription($this->createOption($options));

        return new QuerySubscription($subscription, $this->chronicler);
    }

    public function createEmitterSubscription(string $streamName, array $options = []): EmitterSubscriptionInterface
    {
        $projectionOption = $this->createOption($options);

        $subscription = $this->createGenericSubscription($projectionOption);

        return new EmitterSubscription(
            $subscription,
            $this->createSubscriptionManagement($streamName, $projectionOption),
            $this->createEventCounter($projectionOption),
            $this->chronicler
        );
    }

    public function createReadModelSubscription(string $streamName, ReadModel $readModel, array $options = []): ReadModelSubscriptionInterface
    {
        $projectionOption = $this->createOption($options);

        $subscription = $this->createGenericSubscription($projectionOption);

        return new ReadModelSubscription(
            $subscription,
            $this->createSubscriptionManagement($streamName, $projectionOption),
            $this->createEventCounter($projectionOption),
            $this->chronicler,
            $readModel
        );
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

    protected function createGenericSubscription(ProjectionOption $projectionOption): GenericSubscription
    {
        return new GenericSubscription(
            $projectionOption,
            $this->createStreamManager($projectionOption),
            $this->clock,
            $this->chronicler,
        );
    }

    abstract protected function createSubscriptionManagement(string $streamName, ProjectionOption $options): ProjectionRepositoryInterface;

    protected function createDispatcherRepository(ProjectionRepositoryInterface $projectionRepository): EventDispatcherRepository
    {
        return new EventDispatcherRepository($projectionRepository, $this->dispatcher);
    }

    protected function createLockManager(ProjectionOption $option): LockManager
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
}
