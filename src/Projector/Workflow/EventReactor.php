<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointAdded;
use Chronhub\Storm\Projector\Subscription\Notification\EventIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\GetStreamName;
use Chronhub\Storm\Projector\Subscription\Notification\GetUserState;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintRunning;
use Chronhub\Storm\Projector\Subscription\Notification\IsStateInitialized;
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
    public function __invoke(HookHub $task, DomainEvent $event, int $expectedPosition): bool
    {
        $this->dispatchSignalIfRequested();

        if (! $this->hasNoGap($task, $expectedPosition)) {
            return false;
        }

        $task->listen(EventIncremented::class);

        $this->reactOn($event, $task);

        $this->dispatchWhenThresholdIsReached($task);

        return $task->listen(IsSprintRunning::class);
    }

    private function reactOn(DomainEvent $event, HookHub $task): void
    {
        $initializedState = $task->listen(IsStateInitialized::class)
            ? $task->listen(GetUserState::class) : null;

        $resetScope = ($this->scope)($event, $initializedState);

        ($this->reactors)($this->scope);

        $currentState = $this->scope->getState();

        if (is_array($initializedState) && is_array($currentState)) {
            $task->listen(UserStateChanged::class, $currentState);
        }

        if ($this->scope->isAcked()) {
            $task->listen(StreamEventAcked::class, $event::class);
        }

        $resetScope();
    }

    private function hasNoGap(HookHub $task, int $expectedPosition): bool
    {
        return $task->listen(new CheckpointAdded($task->listen(GetStreamName::class), $expectedPosition));
    }

    private function dispatchWhenThresholdIsReached(HookHub $task): void
    {
        $task->trigger(new ProjectionPersistedWhenThresholdIsReached());
    }

    private function dispatchSignalIfRequested(): void
    {
        if ($this->dispatchSignal) {
            pcntl_signal_dispatch();
        }
    }
}
