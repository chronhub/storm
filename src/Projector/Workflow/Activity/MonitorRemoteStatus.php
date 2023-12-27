<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Subscription\Notification\GetStatus;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintDaemonize;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionClosed;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionDiscarded;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRestarted;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRevised;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionSynchronized;

final class MonitorRemoteStatus
{
    private bool $onRise = true;

    public function shouldStop(HookHub $hub): bool
    {
        $shouldStop = $this->discovering($hub);

        $this->onRise = false;

        return $shouldStop;
    }

    public function refreshStatus(HookHub $hub): void
    {
        $this->onRise = false;

        $this->discovering($hub);
    }

    private function onStopping(HookHub $hub): bool
    {
        if ($this->onRise) {
            $hub->trigger(new ProjectionSynchronized());
        }

        $hub->trigger(new ProjectionClosed());

        return $this->onRise;
    }

    private function onResetting(HookHub $hub): bool
    {
        $hub->trigger(new ProjectionRevised());

        if (! $this->onRise && $hub->interact(IsSprintDaemonize::class)) {
            $hub->trigger(new ProjectionRestarted());
        }

        return false;
    }

    private function onDeleting(HookHub $notification, bool $shouldDiscardEvents): bool
    {
        $notification->trigger(new ProjectionDiscarded($shouldDiscardEvents));

        return $this->onRise;
    }

    private function discovering(HookHub $hub): bool
    {
        $hub->trigger(new ProjectionStatusDisclosed());

        return match ($hub->interact(GetStatus::class)->value) {
            ProjectionStatus::STOPPING->value => $this->onStopping($hub),
            ProjectionStatus::RESETTING->value => $this->onResetting($hub),
            ProjectionStatus::DELETING->value => $this->onDeleting($hub, false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => $this->onDeleting($hub, true),
            default => false,
        };
    }
}
