<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Subscription\Notification;
use Chronhub\Storm\Projector\Subscription\Observer\PersistWhenThresholdIsReached;
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

        $noGap = $notification->onCheckpointAdded($notification->observeStreamName(), $expectedPosition);

        if (! $noGap) {
            return false;
        }

        $notification->onEventIncremented();

        $this->reactOn($event, $notification);

        $notification->dispatch(new PersistWhenThresholdIsReached());

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
