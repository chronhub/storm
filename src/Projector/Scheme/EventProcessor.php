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

        // assume event "handled" and increment counter
        // in fact, with closure reactors, event(s) may have been skipped
        // which make us store the same data, that was the pros of array reactors.

        // the easiest would be to add concerned events in context factory,
        // and increment counter only if event is concerned.
        //  - pros: easy to implement, dev can use these events to optimize query filter
        //  - cons: pita when too many events, can slow down queries filter if used, and if not set, operation store and persist vary
        // this must be handled by the handle stream activity in_array($event->type(), $this->concernedEvents)

        // in next cases, we need to refactor how we detect gap and bind stream and also handle null in handle stream event from the event process
        // cannot bind a stream which is not considered as handled!

        // some solution:
        //   - fetch position and state and compare after reactor if they have changed => user state can be altered or not in too many ways
        //   - bring back array reactors but even more cumbersome now as we do not change the scope anymore.
        //   - make us aware of event handled from scope $scope->eventHandled() and reset it but too much side effect.
        //   - allow return false from closure reactor, but we lost the simplicity of closure as we need to return state or null each time to finally return false
        if ($subscription instanceof PersistentSubscriptionInterface) {
            $subscription->eventCounter()->increment();
        }

        $this->reactOn($event, $subscription);

        // when option block size is reached, persist data
        if ($subscription instanceof PersistentSubscriptionInterface) {
            $subscription->persistWhenCounterIsReached();
        }

        // can return false to stop processing as it may have stopped
        // from a signal or monitor command
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
        // query subscription does not mind of a gap,
        // so bind stream to the next position will always return true
        $eventTime = $subscription instanceof PersistentSubscriptionInterface
            ? $event->header(Header::EVENT_TIME) : false;

        return $subscription->streamManager()->bind($subscription->currentStreamName(), $nextPosition, $eventTime);
    }
}
