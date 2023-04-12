<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Message\MessageAlias;
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
use Chronhub\Storm\Projector\Scheme\StreamGapDetector;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
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
        return new QuerySubscription(
            $this->createOption($options),
            $this->createStreamPosition(),
            $this->clock
        );
    }

    public function createEmitterSubscription(string $streamName, array $options = []): EmitterSubscriptionInterface
    {
        $projectionOption = $this->createOption($options);
        $streamPosition = $this->createStreamPosition();

        return new EmitterSubscription(
            $this->createSubscriptionManagement($streamName, $projectionOption),
            $projectionOption,
            $streamPosition,
            $this->createEventCounter($projectionOption),
            $this->createGapDetector($streamPosition, $projectionOption),
            $this->clock,
            $this->chronicler,
        );
    }

    public function createReadModelSubscription(string $streamName, ReadModel $readModel, array $options = []): ReadModelSubscriptionInterface
    {
        $projectionOption = $this->createOption($options);
        $streamPosition = $this->createStreamPosition();

        return new ReadModelSubscription(
            $this->createSubscriptionManagement($streamName, $projectionOption),
            $projectionOption,
            $streamPosition,
            $this->createEventCounter($projectionOption),
            $this->createGapDetector($streamPosition, $projectionOption),
            $this->clock,
            $readModel
        );
    }

    abstract public function createSubscriptionManagement(string $streamName, ProjectionOption $options): ProjectionRepositoryInterface;

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

    protected function createGapDetector(StreamPosition $streamPosition, ProjectionOption $options): StreamGapDetector
    {
        return new StreamGapDetector(
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

    public function createContextBuilder(): Context
    {
        return new Context();
    }
}
