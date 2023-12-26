<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointReset;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointUpdated;
use Chronhub\Storm\Projector\Subscription\Notification\EventIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\EventReset;
use Chronhub\Storm\Projector\Subscription\Notification\HasBatchStreams;
use Chronhub\Storm\Projector\Subscription\Notification\OriginalUserStateReset;
use Chronhub\Storm\Projector\Subscription\Notification\ProjectionRunning;
use Chronhub\Storm\Projector\Subscription\Notification\ProjectionStopped;
use Chronhub\Storm\Projector\Subscription\Notification\ResetAckedEvent;
use Chronhub\Storm\Projector\Subscription\Notification\ResetBatchStreams;
use Chronhub\Storm\Projector\Subscription\Notification\SleepWhenEmptyBatchStreams;
use Chronhub\Storm\Projector\Subscription\Notification\StatusChanged;
use Chronhub\Storm\Projector\Subscription\Notification\StatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Notification\StreamEventAcked;
use Chronhub\Storm\Projector\Subscription\Notification\StreamsDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateChanged;

final readonly class Notification
{
    public function __construct(private Subscriptor $subscriptor)
    {
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
        return $this->subscriptor->checkPoints();
    }

    public function observeThresholdIsReached(): bool
    {
        return $this->subscriptor->isEventReached();
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
        $this->send(new OriginalUserStateReset());
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

    public function onResetBatchStreams(): void
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

    public function onCheckpointUpdated(array $checkpoints): void
    {
        $this->send(new CheckpointUpdated($checkpoints));
    }

    public function onCheckpointReset(): void
    {
        $this->send(new CheckpointReset());
    }

    public function onProjectionRunning()
    {
        $this->send(new ProjectionRunning());
    }

    public function onProjectionStopped(): void
    {
        $this->send(new ProjectionStopped());
    }

    private function send(object $notification): void
    {
        $this->subscriptor->receive($notification);
    }
}
