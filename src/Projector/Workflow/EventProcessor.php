<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

use function is_array;
use function pcntl_signal_dispatch;

final readonly class EventProcessor
{
    private Closure $reactors;

    private ProjectorScope $scope;

    private ?PersistentManagement $management;

    public function __construct(
        Closure $reactors,
        ProjectorScope $scope,
        ?PersistentManagement $management
    ) {
        $this->reactors = $reactors;
        $this->scope = $scope;
        $this->management = $management;
    }

    /**
     * @param positive-int $expectedPosition
     */
    public function __invoke(Subscription $subscription, DomainEvent $event, int $expectedPosition): bool
    {
        $this->dispatchSignalIfRequested($subscription);

        if (! $subscription->streamManager->insert($subscription->currentStreamName(), $expectedPosition)) {
            return false;
        }

        $this->incrementEventCounter($subscription);

        $this->reactOn($event, $subscription);

        $this->management?->persistWhenCounterIsReached();

        return $subscription->sprint->inProgress();
    }

    private function dispatchSignalIfRequested(Subscription $subscription): void
    {
        if ($subscription->option->getSignal()) {
            pcntl_signal_dispatch();
        }
    }

    private function incrementEventCounter(Subscription $subscription): void
    {
        // checkMe: use if, till event counter is a setter in subscription
        if ($this->management) {
            $subscription->eventCounter->increment();
        }
    }

    private function reactOn(DomainEvent $event, Subscription $subscription): void
    {
        $initializedState = $this->getUserState($subscription);

        $this->scope->setEvent($event);
        if (is_array($initializedState)) {
            $this->scope->setState($initializedState);
        }

        ($this->reactors)($this->scope);
        /*$currentState = is_array($initializedState)
            ? ($this->reactors)($event, $initializedState, $this->scope)
            : ($this->reactors)($event, $this->scope);*/

        $this->updateUserState($subscription, $initializedState, $this->scope->getState());
    }

    private function getUserState(Subscription $subscription): ?array
    {
        return $subscription->context()->userState() instanceof Closure
            ? $subscription->state->get() : null;
    }

    private function updateUserState(Subscription $subscription, ?array $initializedState, ?array $currentState): void
    {
        if (is_array($initializedState) && is_array($currentState)) {
            $subscription->state->put($currentState);
        }
    }
}
