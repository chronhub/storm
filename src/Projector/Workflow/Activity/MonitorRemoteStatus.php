<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\ProjectionStatus;

// todo use notification
final class MonitorRemoteStatus
{
    private bool $onRise = true;

    public function __construct(private readonly PersistentManagement $management)
    {
    }

    public function shouldStop(bool $keepRunning): bool
    {
        $shouldStop = $this->discovering($keepRunning);

        $this->onRise = false;

        return $shouldStop;
    }

    public function refreshStatus(bool $keepRunning): void
    {
        $this->onRise = false;

        $this->discovering($keepRunning);
    }

    private function onStopping(): bool
    {
        if ($this->onRise) {
            // todo why sync on stop,
            $this->management->synchronise();
        }

        $this->management->close();

        return $this->onRise;
    }

    private function onResetting(bool $keepRunning): bool
    {
        $this->management->revise();

        if (! $this->onRise && $keepRunning) {
            $this->management->restart();
        }

        return false;
    }

    private function onDeleting(bool $shouldDiscardEvents): bool
    {
        $this->management->discard($shouldDiscardEvents);

        return $this->onRise;
    }

    private function discovering(bool $keepRunning): bool
    {
        return match ($this->management->disclose()->value) {
            ProjectionStatus::STOPPING->value => $this->onStopping(),
            ProjectionStatus::RESETTING->value => $this->onResetting($keepRunning),
            ProjectionStatus::DELETING->value => $this->onDeleting(false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => $this->onDeleting(true),
            default => false,
        };
    }
}
