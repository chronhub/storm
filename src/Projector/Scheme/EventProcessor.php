<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

use function is_array;
use function pcntl_signal_dispatch;

final readonly class EventProcessor
{
    public function __construct(
        private Closure $reactors,
        private ProjectorScope $scope,
        private ?PersistentManagement $management,
    ) {
    }

    /**
     * @param positive-int $expectedPosition
     */
    public function __invoke(Subscription $subscription, DomainEvent $event, int $expectedPosition): bool
    {
        if ($subscription->option->getSignal()) {
            pcntl_signal_dispatch();
        }

        // gap has been detected
        if (! $subscription->streamManager->bind($subscription->currentStreamName(), $expectedPosition, $event)) {
            return false;
        }

        // increment event counter for each event
        if ($this->management) {
            $subscription->eventCounter->increment();
        }

        // react on event
        $this->reactOn($event, $subscription);

        // when option block size is reached, persist data
        $this->management?->persistWhenCounterIsReached();

        // can return false to stop processing as it may have stopped
        // from a signal or monitor command
        return $subscription->sprint->inProgress();
    }

    private function reactOn(DomainEvent $event, Subscription $subscription): void
    {
        // ensure to pass user state only if it has been initialized
        $userState = $subscription->context()->userState() instanceof Closure
            ? $subscription->state->get() : null;

        // handle event
        $currentState = ! is_array($userState)
            ? ($this->reactors)($event, $this->scope)
            : ($this->reactors)($event, $userState, $this->scope);

        // update user state if it has been initialized and returned
        if (is_array($userState) && is_array($currentState)) {
            $subscription->state->put($currentState);
        }
    }
}
