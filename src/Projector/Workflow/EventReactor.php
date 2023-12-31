<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Stream\Checkpoint;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionPersistedWhenThresholdIsReached;
use Chronhub\Storm\Projector\Subscription\Notification\BatchCounterIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointAdded;
use Chronhub\Storm\Projector\Subscription\Notification\CurrentUserState;
use Chronhub\Storm\Projector\Subscription\Notification\GapDetected;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintRunning;
use Chronhub\Storm\Projector\Subscription\Notification\IsUserStateInitialized;
use Chronhub\Storm\Projector\Subscription\Notification\RecoverableGapDetected;
use Chronhub\Storm\Projector\Subscription\Notification\StreamEventAcked;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateChanged;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

use function in_array;
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
    public function __invoke(NotificationHub $hub, string $streamName, DomainEvent $event, int $expectedPosition): bool
    {
        $this->dispatchSignalIfRequested();

        if (! $this->hasNoGap($hub, $streamName, $event, $expectedPosition)) {
            return false;
        }

        $hub->notify(BatchCounterIncremented::class);

        $this->reactOn($hub, $event);

        $hub->trigger(new ProjectionPersistedWhenThresholdIsReached());

        return $hub->expect(IsSprintRunning::class);
    }

    private function reactOn(NotificationHub $hub, DomainEvent $event): void
    {
        $initializedState = $this->getUserState($hub);

        $resetScope = ($this->scope)($event, $initializedState);

        ($this->reactors)($this->scope);

        $this->updateUserState($hub, $initializedState, $this->scope->getState());

        if ($this->scope->isAcked()) {
            $hub->notify(StreamEventAcked::class, $event::class);
        }

        $resetScope();
    }

    private function hasNoGap(NotificationHub $hub, string $streamName, DomainEvent $event, int $expectedPosition): bool
    {
        $checkPoint = $hub->expect(new CheckpointAdded($streamName, $expectedPosition));

        return $this->notifyGapDetected($hub, $streamName, $event, $expectedPosition, $checkPoint);
    }

    private function getUserState(NotificationHub $hub): ?array
    {
        return $hub->expect(IsUserStateInitialized::class)
            ? $hub->expect(CurrentUserState::class) : null;
    }

    private function updateUserState(NotificationHub $hub, ?array $initializedState, ?array $userState): void
    {
        if (is_array($initializedState) && is_array($userState)) {
            $hub->notify(UserStateChanged::class, $userState);
        }
    }

    private function dispatchSignalIfRequested(): void
    {
        if ($this->dispatchSignal) {
            pcntl_signal_dispatch();
        }
    }

    // rewrite checkpoint manager
    // we should handle when gap is not recoverable
    // count retries left, if one only(handle in next activity, then we can warn of an unsolvable gap)
    private function notifyGapDetected(
        NotificationHub $hub,
        string $streamName,
        DomainEvent $event,
        int $expectedPosition,
        Checkpoint $lastCheckpoint
    ): bool {
        if (in_array($expectedPosition - 1, $lastCheckpoint->gaps, true)) {
            $hub->notify(GapDetected::class, $streamName, $event, $expectedPosition);

            return true;
        }

        if ($lastCheckpoint->position !== $expectedPosition) {
            $hub->notify(RecoverableGapDetected::class, $streamName, $event, $expectedPosition);

            return false;
        }

        return true;
    }
}
