<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\Management;
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

    private Management $management;

    public function __construct(
        Closure $reactors,
        ProjectorScope $scope,
        Management $management
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

        if ($this->management instanceof PersistentManagement) {
            $subscription->eventCounter->increment();
        }

        $this->reactOn($event, $subscription);

        if ($this->management instanceof PersistentManagement) {
            $this->management->persistWhenCounterIsReached();
        }

        return $subscription->sprint->inProgress();
    }

    private function dispatchSignalIfRequested(Subscription $subscription): void
    {
        if ($subscription->option->getSignal()) {
            pcntl_signal_dispatch();
        }
    }

    private function reactOn(DomainEvent $event, Subscription $subscription): void
    {
        $initializedState = $this->getUserState($subscription);

        // todo reset management in constructor scope
        // todo pass one event , the decorator or the event,
        //  as second arg of reactors could be used for string decorator class
        $resetScope = ($this->scope)($this->management, $event, $initializedState);

        ($this->reactors)($this->scope);

        $this->updateUserState($subscription, $initializedState, $this->scope->getState());

        // todo handle isAcked

        $resetScope();
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
