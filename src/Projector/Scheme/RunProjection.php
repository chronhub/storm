<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Projector\Subscription\Subscription;

final readonly class RunProjection
{
    public function __construct(
        private Workflow $workflow,
        private Looper $looper,
        private Metrics $metrics,
        private bool $keepRunning,
    ) {
    }

    public function beginCycle(): void
    {
        do {
            $this->startLooperIfNeeded();

            $inProgress = $this->workflow->process(
                fn (Subscription $subscription): bool => $subscription->sprint->inProgress()
            );

            $this->handleCycleEnd($inProgress);
        } while ($this->keepRunning && $inProgress);
    }

    private function startLooperIfNeeded(): void
    {
        if (! $this->looper->hasStarted()) {
            $this->looper->start();

            $this->metrics->newCycle();
        }
    }

    private function handleCycleEnd(bool $inProgress): void
    {
        if (! $this->keepRunning || ! $inProgress) {
            $this->looper->reset();
            $this->metrics->end();
            dump($this->metrics->getCycles());
        } else {
            $this->looper->next();
            $this->metrics->increment();
        }
    }
}
