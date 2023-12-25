<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\Management;
use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Subscription\Notification\EventIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\StreamEventAcked;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

use function is_array;
use function pcntl_signal_dispatch;

final readonly class EventProcessor
{
    public function __construct(
        private Closure $reactors,
        private ProjectorScope $scope,
        private Management $management
    ) {
    }

    /**
     * @param positive-int $expectedPosition
     */
    public function __invoke(Subscriptor $subscriptor, DomainEvent $event, int $expectedPosition): bool
    {
        $this->dispatchSignalIfRequested($subscriptor);

        if (! $subscriptor->addCheckpoint($subscriptor->getStreamName(), $expectedPosition)) {
            return false;
        }

        $subscriptor->notify(new EventIncremented());

        $this->reactOn($event, $subscriptor);

        if ($this->management instanceof PersistentManagement) {
            $this->management->persistWhenCounterIsReached();
        }

        return $subscriptor->isRunning();
    }

    private function reactOn(DomainEvent $event, Subscriptor $subscriptor): void
    {
        $initializedState = $subscriptor->isUserStateInitialized() ? $subscriptor->getUserState() : null;

        $resetScope = ($this->scope)($event, $initializedState);

        ($this->reactors)($this->scope);

        $currentState = $this->scope->getState();

        if (is_array($initializedState) && is_array($currentState)) {
            $subscriptor->setUserState($currentState);
        }

        if ($this->scope->isAcked()) {
            // todo reset acked event after each cycle
            $subscriptor->notify(new StreamEventAcked($event::class));
        }

        $resetScope();
    }

    private function dispatchSignalIfRequested(Subscriptor $subscriptor): void
    {
        if ($subscriptor->option()->getSignal()) {
            pcntl_signal_dispatch();
        }
    }
}
