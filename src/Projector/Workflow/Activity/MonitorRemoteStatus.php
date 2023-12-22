<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Workflow\Sprint;

final class MonitorRemoteStatus
{
    private bool $onRise = true;

    public function shouldStop(PersistentManagement $management, Sprint $sprint): bool
    {
        $shouldStop = $this->discovering($management, $sprint);

        $this->onRise = false;

        return $shouldStop;
    }

    public function refreshStatus(PersistentManagement $management, Sprint $sprint): void
    {
        $this->onRise = false;

        $this->discovering($management, $sprint);
    }

    private function onStopping(PersistentManagement $management): bool
    {
        if ($this->onRise) {
            // todo why sync on stop,
            $management->synchronise();
        }

        $management->close();

        return $this->onRise;
    }

    private function onResetting(PersistentManagement $management, Sprint $sprint): bool
    {
        $management->revise();

        if (! $this->onRise && $sprint->inBackground()) {
            $management->restart();
        }

        return false;
    }

    private function onDeleting(PersistentManagement $management, bool $shouldDiscardEvents): bool
    {
        $management->discard($shouldDiscardEvents);

        return $this->onRise;
    }

    private function discovering(PersistentManagement $management, Sprint $sprint): bool
    {
        return match ($management->disclose()->value) {
            ProjectionStatus::STOPPING->value => $this->onStopping($management),
            ProjectionStatus::RESETTING->value => $this->onResetting($management, $sprint),
            ProjectionStatus::DELETING->value => $this->onDeleting($management, false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => $this->onDeleting($management, true),
            default => false,
        };
    }
}
