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
            $this->subscription->synchronise();
        }

        $this->subscription->close();

        return $this->isFirstCycle;
    }

    private function onResetting(): bool
    {
        $this->subscription->revise();

        if (! $this->isFirstCycle && $this->sprint->inBackground()) {
            $this->subscription->restart();
        }

        return false;
    }

    private function onDeleting(bool $shouldDiscardEvents): bool
    {
        $this->subscription->discard($shouldDiscardEvents);

        return $this->isFirstCycle;
    }

    private function discovering(): bool
    {
        return match ($this->subscription->disclose()->value) {
            ProjectionStatus::STOPPING->value => $this->onStopping(),
            ProjectionStatus::RESETTING->value => $this->onResetting(),
            ProjectionStatus::DELETING->value => $this->onDeleting(false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => $this->onDeleting(true),
            default => false,
        };
    }
}
