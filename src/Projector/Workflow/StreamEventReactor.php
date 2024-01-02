<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\PersistentProjectorScope;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Stream\GapType;
use Chronhub\Storm\Projector\Stream\ShouldSnapshotCheckpoint;
use Chronhub\Storm\Projector\Subscription\Batch\BatchCounterIncremented;
use Chronhub\Storm\Projector\Subscription\Checkpoint\CheckpointInserted;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionPersistedWhenThresholdIsReached;
use Chronhub\Storm\Projector\Subscription\Sprint\IsSprintRunning;
use Chronhub\Storm\Projector\Subscription\Stream\StreamEventAcked;
use Chronhub\Storm\Projector\Subscription\UserState\CurrentUserState;
use Chronhub\Storm\Projector\Subscription\UserState\IsUserStateInitialized;
use Chronhub\Storm\Projector\Subscription\UserState\UserStateChanged;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;
use DateTimeImmutable;

use function is_array;
use function pcntl_signal_dispatch;

class StreamEventReactor
{
    public function __construct(
        protected readonly Closure $reactors,
        protected readonly ProjectorScope $scope,
        protected readonly bool $dispatchSignal
    ) {
    }

    /**
     * @param positive-int $expectedPosition
     */
    public function __invoke(NotificationHub $hub, string $streamName, DomainEvent $event, int $expectedPosition): bool
    {
        $this->dispatchSignalIfRequested();

        if (! $this->hasNoGap($hub, $streamName, $expectedPosition, $event->header(Header::EVENT_TIME))) {
            return false;
        }

        return $this->handleEvent($hub, $event);
    }

    private function handleEvent(NotificationHub $hub, DomainEvent $event): bool
    {
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

    private function hasNoGap(NotificationHub $hub, string $streamName, int $expectedPosition, string|DateTimeImmutable $eventTime): bool
    {
        $checkPoint = $hub->expect(new CheckpointInserted($streamName, $expectedPosition, $eventTime));

        if ($checkPoint->type === null || $checkPoint->type === GapType::IN_GAP) {
            $hub->notifyWhen(
                $this->scope instanceof PersistentProjectorScope,
                fn (NotificationHub $hub) => $hub->notify(ShouldSnapshotCheckpoint::class, $checkPoint)
            );

            return true;
        }

        return false;
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
