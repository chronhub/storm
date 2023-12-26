<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Subscription\Notification;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionClosed;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionDiscarded;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRestarted;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRevised;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionSynchronized;

final class MonitorRemoteStatus
{
    private bool $onRise = true;

    public function shouldStop(Notification $notification): bool
    {
        $shouldStop = $this->discovering($notification);

        $this->onRise = false;

        return $shouldStop;
    }

    public function refreshStatus(Notification $notification): void
    {
        $this->onRise = false;

        $this->discovering($notification);
    }

    private function onStopping(Notification $notification): bool
    {
        if ($this->onRise) {
            // todo why sync on stop,
            $notification->dispatch(new ProjectionSynchronized());
        }

        $notification->dispatch(new ProjectionClosed());

        return $this->onRise;
    }

    private function onResetting(Notification $notification): bool
    {
        $notification->dispatch(new ProjectionRevised());

        if (! $this->onRise && $notification->isInBackground()) {
            $notification->dispatch(new ProjectionRestarted());
        }

        return false;
    }

    private function onDeleting(Notification $notification, bool $shouldDiscardEvents): bool
    {
        $notification->dispatch(new ProjectionDiscarded($shouldDiscardEvents));

        return $this->onRise;
    }

    private function discovering(Notification $notification): bool
    {
        $notification->dispatch(new ProjectionStatusDisclosed());

        return match ($notification->observeStatus()->value) {
            ProjectionStatus::STOPPING->value => $this->onStopping($notification),
            ProjectionStatus::RESETTING->value => $this->onResetting($notification),
            ProjectionStatus::DELETING->value => $this->onDeleting($notification, false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => $this->onDeleting($notification, true),
            default => false,
        };
    }
}
