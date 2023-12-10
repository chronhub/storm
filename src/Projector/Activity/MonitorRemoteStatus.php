<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\ProjectionStatus;

trait MonitorRemoteStatus
{
    private bool $isFirstCycle = true;

    protected function shouldStopOnDiscoveringStatus(): bool
    {
        $shouldStop = $this->discovering();

        $this->isFirstCycle = false;

        return $shouldStop;
    }

    protected function refreshStatus(): void
    {
        $this->isFirstCycle = false;

        $this->discovering();
    }

    protected function isFirstCycle(): bool
    {
        return $this->isFirstCycle;
    }

    private function onStopping(): bool
    {
        if ($this->isFirstCycle) {
            $this->management->synchronise();
        }

        $this->management->close();

        return $this->isFirstCycle;
    }

    private function onResetting(): bool
    {
        $this->management->revise();

        if (! $this->isFirstCycle && $this->sprint->inBackground()) {
            $this->management->restart();
        }

        return false;
    }

    private function onDeleting(bool $shouldDiscardEvents): bool
    {
        $this->management->discard($shouldDiscardEvents);

        return $this->isFirstCycle;
    }

    private function discovering(): bool
    {
        return match ($this->management->disclose()->value) {
            ProjectionStatus::STOPPING->value => $this->onStopping(),
            ProjectionStatus::RESETTING->value => $this->onResetting(),
            ProjectionStatus::DELETING->value => $this->onDeleting(false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => $this->onDeleting(true),
            default => false,
        };
    }
}
