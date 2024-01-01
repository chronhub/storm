<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Stream\GapType;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionPersistedWhenThresholdIsReached;
use Chronhub\Storm\Projector\Subscription\Notification\BatchCounterIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointAdded;
use Chronhub\Storm\Projector\Subscription\Notification\CurrentUserState;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintRunning;
use Chronhub\Storm\Projector\Subscription\Notification\IsUserStateInitialized;
use Chronhub\Storm\Projector\Subscription\Notification\StreamEventAcked;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateChanged;
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
    public function __invoke(NotificationHub $hub, string $streamName, DomainEvent $event, int $expectedPosition): bool
    {
        $this->dispatchSignalIfRequested();

        if (! $this->hasNoGap($hub, $streamName, $expectedPosition)) {
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

        $hub->notifyWhen($this->scope->isAcked(), function (NotificationHub $hub) use ($event): void {
            $hub->notify(StreamEventAcked::class, $event::class);
        });

        $resetScope();
    }

    private function hasNoGap(NotificationHub $hub, string $streamName, int $expectedPosition): bool
    {
        $checkPoint = $hub->expect(new CheckpointAdded($streamName, $expectedPosition));

        return $checkPoint->type === null || $checkPoint->type === GapType::IN_GAP;
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
}
