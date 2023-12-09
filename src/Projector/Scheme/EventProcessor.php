<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\Subscriber;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

use function is_array;
use function pcntl_signal_dispatch;

final readonly class EventProcessor
{
    public function __construct(
        private Closure $reactors,
        private ProjectorScope $scope,
        private ?SubscriptionManagement $subscription = null,
    ) {
    }

    /**
     * @param positive-int $expectedPosition
     */
    public function __invoke(Subscriber $subscriber, DomainEvent $event, int $expectedPosition): bool
    {
        if ($subscriber->option->getSignal()) {
            pcntl_signal_dispatch();
        }

        // gap has been detected for persistent subscription
        if (! $this->bindStream($subscriber, $event, $expectedPosition)) {
            return false;
        }

        if ($subscriber instanceof PersistentSubscriber) {
            $subscriber->eventCounter->increment();
        }

        // react on event
        $this->reactOn($event, $subscriber);

        // when option block size is reached, persist data
        $this->subscription?->persistWhenCounterIsReached();

        // can return false to stop processing as it may have stopped
        // from a signal or monitor command
        return $subscriber->sprint->inProgress();
    }

    private function reactOn(DomainEvent $event, Subscriber $subscriber): void
    {
        // ensure to pass user state only if it has been initialized
        $userState = $subscriber->context()->userState() instanceof Closure
            ? $subscriber->state->get() : null;

        // handle event
        $currentState = $userState === null
            ? ($this->reactors)($event, $this->scope)
            : ($this->reactors)($event, $userState, $this->scope);

        // update user state if it has been initialized and returned
        if ($userState !== null && is_array($currentState)) {
            $subscriber->state->put($currentState);
        }
    }

    /**
     * Bind the current stream name to the expected position if match
     */
    private function bindStream(Subscriber $subscriber, DomainEvent $event, int $nextPosition): bool
    {
        // query subscription does not mind of a gap,
        // so bind stream to the next position will always return true
        $eventTime = $this->subscription !== null ? $event->header(Header::EVENT_TIME) : false;

        return $subscriber->streamBinder->bind($subscriber->currentStreamName(), $nextPosition, $eventTime);
    }
}
