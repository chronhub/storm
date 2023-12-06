<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

use function is_array;
use function pcntl_signal_dispatch;

final readonly class EventProcessor
{
    public function __construct(
        private Closure $reactors,
        private ProjectorScope $scope
    ) {
    }

    /**
     * @param positive-int $expectedPosition
     */
    public function __invoke(Subscription $subscription, DomainEvent $event, int $expectedPosition): bool
    {
        if ($subscription->option()->getSignal()) {
            pcntl_signal_dispatch();
        }

        // gap has been detected for persistent subscription
        if (! $this->bindStream($subscription, $event, $expectedPosition)) {
            return false;
        }

        $this->reactOn($event, $subscription);

        // event handled and increment counter
        if ($subscription instanceof PersistentSubscriptionInterface) {
            $subscription->eventCounter()->increment();
        }

        // when option block size is reached, persist data
        if ($subscription instanceof PersistentSubscriptionInterface) {
            $subscription->persistWhenCounterIsReached();
        }

        /**
         * Can return false to stop processing as it may have stopped
         * from a signal or monitor command
         */
        return $subscription->sprint()->inProgress();
    }

    private function reactOn(DomainEvent $event, Subscription $subscription): void
    {
        // ensure to pass user state only if it has been initialized
        $userState = $subscription->context()->userState() instanceof Closure
            ? $subscription->state()->get() : null;

        // handle event
        $currentState = $userState === null
            ? ($this->reactors)($event, $this->scope)
            : ($this->reactors)($event, $userState, $this->scope);

        // update user state if it has been initialized and returned
        if ($userState !== null && is_array($currentState)) {
            $subscription->state()->put($currentState);
        }
    }

    /**
     * Bind the current stream name to the expected position if match
     */
    private function bindStream(Subscription $subscription, DomainEvent $event, int $nextPosition): bool
    {
        // query subscription does not mind of gaps
        $eventTime = $subscription instanceof PersistentSubscriptionInterface
            ? $event->header(Header::EVENT_TIME) : false;

        return $subscription->streamManager()->bind($subscription->currentStreamName(), $nextPosition, $eventTime);
    }
}
