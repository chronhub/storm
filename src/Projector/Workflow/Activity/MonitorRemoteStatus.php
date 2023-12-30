<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionClosed;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionDiscarded;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionRestarted;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionRevised;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionStatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionSynchronized;
use Chronhub\Storm\Projector\Subscription\Notification\CurrentStatus;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintDaemonize;

trait MonitorRemoteStatus
{
    private bool $onRise = true;

    public function shouldStop(NotificationHub $hub): bool
    {
        $shouldStop = $this->discovering($hub);

        $this->onRise = false;

        return $shouldStop;
    }

    public function refreshStatus(NotificationHub $hub): void
    {
        $this->onRise = false;

        $this->discovering($hub);
    }

    private function onStopping(NotificationHub $hub): bool
    {
        if ($this->onRise) {
            $hub->trigger(new ProjectionSynchronized());
        }

        $hub->trigger(new ProjectionClosed());

        return $this->onRise;
    }

    private function onResetting(NotificationHub $hub): bool
    {
        $hub->trigger(new ProjectionRevised());

        if (! $this->onRise && $hub->expect(IsSprintDaemonize::class)) {
            $hub->trigger(new ProjectionRestarted());
        }

        return false;
    }

    private function onDeleting(NotificationHub $notification, bool $shouldDiscardEvents): bool
    {
        $notification->trigger(new ProjectionDiscarded($shouldDiscardEvents));

        return $this->onRise;
    }

    private function discovering(NotificationHub $hub): bool
    {
        $hub->trigger(new ProjectionStatusDisclosed());

        return match ($hub->expect(CurrentStatus::class)->value) {
            ProjectionStatus::STOPPING->value => $this->onStopping($hub),
            ProjectionStatus::RESETTING->value => $this->onResetting($hub),
            ProjectionStatus::DELETING->value => $this->onDeleting($hub, false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => $this->onDeleting($hub, true),
            default => false,
        };
    }
}
