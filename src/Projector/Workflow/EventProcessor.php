<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\Management;
use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Subscription\Notification;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

use function is_array;
use function pcntl_signal_dispatch;

final readonly class EventProcessor
{
    public function __construct(
        private Closure $reactors,
        private ProjectorScope $scope,
        private Management $management,
        private bool $dispatchSignal
    ) {
    }

    /**
     * @param positive-int $expectedPosition
     */
    public function __invoke(Notification $notification, DomainEvent $event, int $expectedPosition): bool
    {
        $this->dispatchSignalIfRequested();

        $isCheckpointValid = $notification->onCheckpointAdded(
            $notification->observeStreamName(),
            $expectedPosition
        );

        if (! $isCheckpointValid) {
            return false;
        }

        $notification->onEventIncremented();

        $this->reactOn($event, $notification);

        if ($this->management instanceof PersistentManagement) {
            $notification->dispatch(new \Chronhub\Storm\Projector\Subscription\Observer\PersistWhenThresholdIsReached());
        }

        return $notification->isRunning();
    }

    private function reactOn(DomainEvent $event, Notification $notification): void
    {
        $initializedState = $notification->observeUserState();

        $resetScope = ($this->scope)($event, $initializedState);

        ($this->reactors)($this->scope);

        $currentState = $this->scope->getState();

        if (is_array($initializedState) && is_array($currentState)) {
            $notification->onUserStateChanged($currentState);
        }

        if ($this->scope->isAcked()) {
            $notification->onStreamEventAcked($event::class);
        }

        $resetScope();
    }

    private function dispatchSignalIfRequested(): void
    {
        if ($this->dispatchSignal) {
            pcntl_signal_dispatch();
        }
    }
}
