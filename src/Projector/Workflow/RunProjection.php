<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintRunning;
use Chronhub\Storm\Projector\Support\Loop;

final readonly class RunProjection
{
    public function __construct(
        private Workflow $workflow,
        private Loop $loop,
        private bool $keepRunning,
    ) {
    }

    public function loop(): void
    {
        do {
            $this->startLooperIfNeeded();

            $inProgress = $this->workflow->process(
                fn (HookHub $hub): bool => $hub->listen(IsSprintRunning::class)
            );

            $this->handleCycleEnd($inProgress);
        } while ($this->keepRunning && $inProgress);
    }

    private function startLooperIfNeeded(): void
    {
        if (! $this->loop->hasStarted()) {
            $this->loop->start();
        }
    }

    private function handleCycleEnd(bool $inProgress): void
    {
        ! $this->keepRunning || ! $inProgress
           ? $this->loop->reset() : $this->loop->next();
    }
}
