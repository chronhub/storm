<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\QuerySubscriber;
use Chronhub\Storm\Projector\Subscription\Beacon;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

use function is_array;
use function pcntl_signal_dispatch;

final readonly class EventProcessor
{
    public function __construct(
        private QuerySubscriber|PersistentSubscriber $subscription,
        private Closure $reactors,
        private ProjectorScope $scope
    ) {
    }

    /**
     * @param positive-int $expectedPosition
     */
    public function __invoke(Beacon $manager, DomainEvent $event, int $expectedPosition): bool
    {
        if ($manager->option->getSignal()) {
            pcntl_signal_dispatch();
        }

        // gap has been detected for persistent subscription
        if (! $this->bindStream($manager, $event, $expectedPosition)) {
            return false;
        }

        if ($this->subscription instanceof PersistentSubscriber) {
            $this->subscription->eventCounter()->increment();
        }

        $this->reactOn($event, $manager);

        // when option block size is reached, persist data
        if ($this->subscription instanceof PersistentSubscriber) {
            $this->subscription->persistWhenCounterIsReached();
        }

        // can return false to stop processing as it may have stopped
        // from a signal or monitor command
        return $manager->sprint->inProgress();
    }

    private function reactOn(DomainEvent $event, Beacon $manager): void
    {
        // ensure to pass user state only if it has been initialized
        $userState = $manager->context()->userState() instanceof Closure
            ? $manager->state()->get() : null;

        // handle event
        $currentState = $userState === null
            ? ($this->reactors)($event, $this->scope)
            : ($this->reactors)($event, $userState, $this->scope);

        // update user state if it has been initialized and returned
        if ($userState !== null && is_array($currentState)) {
            $manager->state()->put($currentState);
        }
    }

    /**
     * Bind the current stream name to the expected position if match
     */
    private function bindStream(Beacon $manager, DomainEvent $event, int $nextPosition): bool
    {
        // query subscription does not mind of a gap,
        // so bind stream to the next position will always return true
        $eventTime = $this->subscription instanceof PersistentSubscriber
            ? $event->header(Header::EVENT_TIME) : false;

        return $manager->streamBinder->bind($manager->currentStreamName(), $nextPosition, $eventTime);
    }
}
