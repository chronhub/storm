<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\PersistentProjectorScope;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Checkpoint\Checkpoint;
use Chronhub\Storm\Projector\Checkpoint\GapType;
use Chronhub\Storm\Projector\Checkpoint\ShouldSnapshotCheckpoint;
use Chronhub\Storm\Projector\Support\Notification\Batch\BatchIncremented;
use Chronhub\Storm\Projector\Support\Notification\Checkpoint\CheckpointInserted;
use Chronhub\Storm\Projector\Support\Notification\Management\ProjectionPersistedWhenThresholdIsReached;
use Chronhub\Storm\Projector\Support\Notification\Sprint\IsSprintRunning;
use Chronhub\Storm\Projector\Support\Notification\Stream\StreamEventAcked;
use Chronhub\Storm\Projector\Support\Notification\UserState\CurrentUserState;
use Chronhub\Storm\Projector\Support\Notification\UserState\IsUserStateInitialized;
use Chronhub\Storm\Projector\Support\Notification\UserState\UserStateChanged;
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

    protected function handleEvent(NotificationHub $hub, DomainEvent $event): bool
    {
        $hub->notify(BatchIncremented::class);

        $this->reactOn($hub, $event);

        $hub->trigger(new ProjectionPersistedWhenThresholdIsReached());

        return $hub->expect(IsSprintRunning::class);
    }

    protected function reactOn(NotificationHub $hub, DomainEvent $event): void
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

    protected function hasNoGap(NotificationHub $hub, string $streamName, int $expectedPosition, string|DateTimeImmutable $eventTime): bool
    {
        $checkPoint = $hub->expect(new CheckpointInserted($streamName, $expectedPosition, $eventTime));

        return $this->onCheckpointInserted($hub, $checkPoint);
    }

    protected function getUserState(NotificationHub $hub): ?array
    {
        return $hub->expect(IsUserStateInitialized::class)
            ? $hub->expect(CurrentUserState::class) : null;
    }

    protected function updateUserState(NotificationHub $hub, ?array $initializedState, ?array $userState): void
    {
        if (is_array($initializedState) && is_array($userState)) {
            $hub->notify(UserStateChanged::class, $userState);
        }
    }

    protected function onCheckpointInserted(NotificationHub $hub, Checkpoint $checkpoint): bool
    {
        if ($checkpoint->type === null || $checkpoint->type === GapType::IN_GAP) {
            $hub->notifyWhen(
                $this->scope instanceof PersistentProjectorScope,
                fn (NotificationHub $hub) => $hub->notify(ShouldSnapshotCheckpoint::class, $checkpoint)
            );

            return true;
        }

        return false;
    }

    protected function dispatchSignalIfRequested(): void
    {
        if ($this->dispatchSignal) {
            pcntl_signal_dispatch();
        }
    }
}
