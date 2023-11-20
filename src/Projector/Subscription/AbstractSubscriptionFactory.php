<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Options\DefaultProjectionOption;
use Chronhub\Storm\Projector\Repository\LockManager;
use Chronhub\Storm\Projector\Repository\ProjectionRepository;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\EventStreamLoader;
use Chronhub\Storm\Projector\Scheme\StreamManager;

use function array_merge;

abstract class AbstractSubscriptionFactory
{
    public function __construct(
        public readonly Chronicler $chronicler,
        public readonly ProjectionProvider $projectionProvider,
        public readonly EventStreamProvider $eventStreamProvider,
        public readonly ProjectionQueryScope $queryScope,
        public readonly SystemClock $clock,
        public readonly MessageAlias $messageAlias,
        public readonly JsonSerializer $jsonSerializer,
        public readonly ProjectionOption|array $options = []
    ) {
    }

    public function createQuerySubscription(array $options = []): Subscription
    {
        $subscription = $this->createGenericSubscription($this->createOption($options));

        return new QuerySubscription($subscription);
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

    public function createContextBuilder(): ContextInterface
    {
        return new Context();
    }

    protected function createGenericSubscription(ProjectionOption $projectionOption): GenericSubscription
    {
        return new GenericSubscription(
            $projectionOption,
            $this->createStreamPosition($projectionOption),
            $this->clock,
            $this->chronicler,
        );
    }

    abstract protected function createSubscriptionManagement(string $streamName, ProjectionOption $options): ProjectionRepositoryInterface;

    protected function createRepository(string $streamName, ProjectionOption $options): ProjectionRepositoryInterface
    {
        return new ProjectionRepository(
            $this->projectionProvider,
            $this->createLockManager($options),
            $this->jsonSerializer,
            $streamName
        );
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

    protected function createStreamPosition(ProjectionOption $options): StreamManager
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
