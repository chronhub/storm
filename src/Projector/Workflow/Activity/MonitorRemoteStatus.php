<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Support\Notification\Management\ProjectionClosed;
use Chronhub\Storm\Projector\Support\Notification\Management\ProjectionDiscarded;
use Chronhub\Storm\Projector\Support\Notification\Management\ProjectionRestarted;
use Chronhub\Storm\Projector\Support\Notification\Management\ProjectionRevised;
use Chronhub\Storm\Projector\Support\Notification\Management\ProjectionStatusDisclosed;
use Chronhub\Storm\Projector\Support\Notification\Management\ProjectionSynchronized;
use Chronhub\Storm\Projector\Support\Notification\Sprint\IsSprintDaemonize;
use Chronhub\Storm\Projector\Support\Notification\Status\CurrentStatus;

trait MonitorRemoteStatus
{
    private bool $onRise = true;

    /**
     * Stop projection early if remote status is stopping or deleting
     */
    public function shouldStop(NotificationHub $hub): bool
    {
        $shouldStop = $this->discovering($hub);

        $this->onRise = false;

        return $shouldStop;
    }

    /**
     * Refresh projection status at the end of each cycle
     */
    public function refreshStatus(NotificationHub $hub): void
    {
        $this->onRise = false;

        $this->discovering($hub);
    }

    protected function onStopping(NotificationHub $hub): bool
    {
        if ($this->onRise) {
            $hub->trigger(new ProjectionSynchronized());
        }

        $hub->trigger(new ProjectionClosed());

        return $this->onRise;
    }

    protected function onResetting(NotificationHub $hub): bool
    {
        $hub->trigger(new ProjectionRevised());

        if (! $this->onRise && $hub->expect(IsSprintDaemonize::class)) {
            $hub->trigger(new ProjectionRestarted());
        }

        return false;
    }

    protected function onDeleting(NotificationHub $notification, bool $shouldDiscardEvents): bool
    {
        $notification->trigger(new ProjectionDiscarded($shouldDiscardEvents));

        return $this->onRise;
    }

    /**
     * Discover remote status and act accordingly
     */
    protected function discovering(NotificationHub $hub): bool
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
