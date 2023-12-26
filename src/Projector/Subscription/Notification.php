<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointAdded;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointReset;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointUpdated;
use Chronhub\Storm\Projector\Subscription\Notification\EventIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\EventReset;
use Chronhub\Storm\Projector\Subscription\Notification\HasBatchStreams;
use Chronhub\Storm\Projector\Subscription\Notification\ResetAckedEvent;
use Chronhub\Storm\Projector\Subscription\Notification\ResetBatchStreams;
use Chronhub\Storm\Projector\Subscription\Notification\SleepWhenEmptyBatchStreams;
use Chronhub\Storm\Projector\Subscription\Notification\SprintRunning;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Notification\StatusChanged;
use Chronhub\Storm\Projector\Subscription\Notification\StatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Notification\StreamEventAcked;
use Chronhub\Storm\Projector\Subscription\Notification\StreamProcessed;
use Chronhub\Storm\Projector\Subscription\Notification\StreamsDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateChanged;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateReset;
use Chronhub\Storm\Projector\Subscription\Observer\EventEmitted;
use Chronhub\Storm\Projector\Subscription\Observer\EventLinkedTo;
use Chronhub\Storm\Projector\Subscription\Observer\PersistWhenThresholdIsReached;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionClosed;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionDiscarded;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionFreed;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRestarted;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRevised;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRise;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStored;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionSynchronized;

use function array_key_exists;

final class Notification
{
    /**
     * @var array<string, array<callable>>
     */
    private array $listeners = [
        ProjectionRise::class => [],
        ProjectionStored::class => [],
        ProjectionClosed::class => [],
        ProjectionRevised::class => [],
        ProjectionDiscarded::class => [],
        ProjectionFreed::class => [],
        ProjectionRestarted::class => [],
        ProjectionLockUpdated::class => [],
        ProjectionSynchronized::class => [],
        ProjectionStatusDisclosed::class => [],
        PersistWhenThresholdIsReached::class => [],
        EventEmitted::class => [],
        EventLinkedTo::class => [],
    ];

    public function __construct(private readonly Subscriptor $subscriptor)
    {
    }

    public function isRunning(): bool
    {
        return $this->subscriptor->isRunning();
    }

    public function isInBackground(): bool
    {
        return $this->subscriptor->inBackground();
    }

    public function isRising(): bool
    {
        return $this->subscriptor->isRising();
    }

    public function onStreamMerged(MergeStreamIterator $iterator): void
    {
        $this->subscriptor->setStreamIterator($iterator);
    }

    public function pullStreams(): ?MergeStreamIterator
    {
        return $this->subscriptor->pullStreamIterator();
    }

    public function hasGap(): bool
    {
        return $this->subscriptor->hasGap();
    }

    public function IsEventReset(): bool
    {
        return $this->subscriptor->isEventReset();
    }

    public function listen(string $event, callable $listener): void
    {
        if (! array_key_exists($event, $this->listeners)) {
            throw new RuntimeException("Event $event is not supported");
        }

        $this->listeners[$event][] = $listener;
    }

    public function dispatch(object $event): void
    {
        $eventClass = $event::class;

        if (! array_key_exists($eventClass, $this->listeners)) {
            throw new RuntimeException("Event $eventClass is not supported");
        }

        foreach ($this->listeners[$eventClass] as $handler) {
            $handler($event);
        }
    }

    public function notify(string $notification, mixed ...$arguments): void
    {
        $notifier = new $notification(...$arguments);

        $this->send($notifier);
    }

    public function observeStreamName(): string
    {
        return $this->subscriptor->getStreamName();
    }

    public function observeStatus(): ProjectionStatus
    {
        return $this->subscriptor->currentStatus();
    }

    public function observeUserState(): ?array
    {
        if ($this->subscriptor->isUserStateInitialized()) {
            return $this->subscriptor->getUserState();
        }

        return null;
    }

    public function observeCheckpoints(): array
    {
        return $this->subscriptor->checkpoints();
    }

    public function observeThresholdIsReached(): bool
    {
        return $this->subscriptor->isEventReached();
    }

    public function observeShouldSleepWhenGap(): bool
    {
        return $this->subscriptor->sleepWhenGap();
    }

    public function onStatusChanged(ProjectionStatus $oldStatus, ProjectionStatus $newStatus): void
    {
        $this->send(new StatusChanged($oldStatus, $newStatus));
    }

    public function onStatusDisclosed(ProjectionStatus $oldStatus, ProjectionStatus $newStatus): void
    {
        $this->send(new StatusDisclosed($oldStatus, $newStatus));
    }

    public function onUserStateChanged(array $userState): void
    {
        $this->send(new UserStateChanged($userState));
    }

    public function onOriginalUserStateReset(): void
    {
        $this->send(new UserStateReset());
    }

    public function onEventIncremented(): void
    {
        $this->send(new EventIncremented());
    }

    public function onEventReset(): void
    {
        $this->send(new EventReset());
    }

    public function onHasBatchStreams($hasBatchStreams): void
    {
        $this->send(new HasBatchStreams($hasBatchStreams));
    }

    public function onBatchStreamsReset(): void
    {
        $this->send(new ResetBatchStreams());
    }

    public function onSleepWhenEmptyBatchStreams(): void
    {
        $this->send(new SleepWhenEmptyBatchStreams());
    }

    public function onStreamEventAcked(string $streamEventId): void
    {
        $this->send(new StreamEventAcked($streamEventId));
    }

    public function onResetAckedEvent(): void
    {
        $this->send(new ResetAckedEvent());
    }

    public function onStreamsDiscovered(): void
    {
        $this->send(new StreamsDiscovered());
    }

    public function onCheckpointAdded(string $streamName, int $position): bool
    {
        return $this->subscriptor->receive(new CheckpointAdded($streamName, $position));
    }

    public function onCheckpointUpdated(array $checkpoints): void
    {
        $this->send(new CheckpointUpdated($checkpoints));
    }

    public function onCheckpointReset(): void
    {
        $this->send(new CheckpointReset());
    }

    public function onProjectionRunning(): void
    {
        $this->send(new SprintRunning());
    }

    public function onProjectionStopped(): void
    {
        $this->send(new SprintStopped());
    }

    public function onStreamProcess(string $streamName): void
    {
        $this->send(new StreamProcessed($streamName));
    }

    private function send(object $notification): void
    {
        $this->subscriptor->receive($notification);
    }
}
