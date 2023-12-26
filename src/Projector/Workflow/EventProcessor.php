<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Subscription\Notification;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionPersistedWhenThresholdIsReached;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

use function is_array;
use function pcntl_signal_dispatch;

final readonly class EventProcessor
{
    public function __construct(
        private Closure $reactors,
        private ProjectorScope $scope,
        private bool $dispatchSignal
    ) {
    }

    /**
     * @param positive-int $expectedPosition
     */
    public function __invoke(Notification $notification, DomainEvent $event, int $expectedPosition): bool
    {
        $this->dispatchSignalIfRequested();

        if (! $this->hasNoGap($notification, $expectedPosition)) {
            return false;
        }

        $notification->onEventIncremented();

        $this->reactOn($event, $notification);

        $this->dispatchWhenThresholdIsReached($notification);

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

    private function hasNoGap(Notification $notification, int $expectedPosition): bool
    {
        return $notification->onCheckpointAdded($notification->observeStreamName(), $expectedPosition);
    }

    private function dispatchWhenThresholdIsReached(Notification $notification): void
    {
        $notification->dispatch(new ProjectionPersistedWhenThresholdIsReached());
    }

    private function dispatchSignalIfRequested(): void
    {
        if ($this->dispatchSignal) {
            pcntl_signal_dispatch();
        }
    }
}
