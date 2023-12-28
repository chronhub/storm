<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointAdded;
use Chronhub\Storm\Projector\Subscription\Notification\EventIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\GetProcessedStream;
use Chronhub\Storm\Projector\Subscription\Notification\GetUserState;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintRunning;
use Chronhub\Storm\Projector\Subscription\Notification\IsUserStateInitialized;
use Chronhub\Storm\Projector\Subscription\Notification\StreamEventAcked;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateChanged;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionPersistedWhenThresholdIsReached;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

use function is_array;
use function pcntl_signal_dispatch;

final readonly class EventReactor
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
    public function __invoke(HookHub $hub, DomainEvent $event, int $expectedPosition): bool
    {
        $this->dispatchSignalIfRequested();

        if (! $this->hasNoGap($hub, $expectedPosition)) {
            return false;
        }

        $hub->interact(EventIncremented::class);

        $this->reactOn($hub, $event);

        $this->dispatchWhenThresholdIsReached($hub);

        return $hub->interact(IsSprintRunning::class);
    }

    private function reactOn(HookHub $hub, DomainEvent $event): void
    {
        $initializedState = $this->getUserState($hub);

        $resetScope = ($this->scope)($event, $initializedState);

        ($this->reactors)($this->scope);

        $this->updateUserState($hub, $initializedState, $this->scope->getState());

        if ($this->scope->isAcked()) {
            $hub->interact(StreamEventAcked::class, $event::class);
        }

        $resetScope();
    }

    private function hasNoGap(HookHub $hub, int $expectedPosition): bool
    {
        return $hub->interact(
            new CheckpointAdded($hub->interact(GetProcessedStream::class), $expectedPosition)
        );
    }

    private function dispatchWhenThresholdIsReached(HookHub $hub): void
    {
        $hub->trigger(new ProjectionPersistedWhenThresholdIsReached());
    }

    private function getUserState(HookHub $hub): ?array
    {
        return $hub->interact(IsUserStateInitialized::class)
            ? $hub->interact(GetUserState::class) : null;
    }

    private function updateUserState(HookHub $hub, ?array $initializedState, ?array $userState): void
    {
        if (is_array($initializedState) && is_array($userState)) {
            $hub->interact(UserStateChanged::class, $userState);
        }
    }

    private function dispatchSignalIfRequested(): void
    {
        if ($this->dispatchSignal) {
            pcntl_signal_dispatch();
        }
    }
}
