<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Throwable;

final readonly class RunProjection
{
    public function __construct(
        private Workflow $workflow,
        private Looper $looper,
        private bool $keepRunning,
        private ?PersistentManagement $management,
    ) {
    }

    public function beginCycle(): void
    {
        try {
            $this->runWorkflowCycle();
        } catch (Throwable $exception) {
            $this->handleException($exception);
        } finally {
            $this->tryReleaseLock();
        }
    }

    private function runWorkflowCycle(): void
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
        }
    }

    private function handleCycleEnd(bool $inProgress): void
    {
        if (! $this->keepRunning || ! $inProgress) {
            $this->looper->reset();
        } else {
            $this->looper->next();
        }
    }

    /**
     * @throws Throwable
     */
    private function handleException(Throwable $exception): void
    {
        if (! $exception instanceof ProjectionAlreadyRunning && $this->management) {
            $this->trySilentReleaseLock();
        }

        throw $exception;
    }

    private function trySilentReleaseLock(): void
    {
        try {
            $this->management->freed();
        } catch (Throwable) {
            // Fail silently
        }
    }

    private function tryReleaseLock(): void
    {
        if (! $this->management) {
            return;
        }

        $this->trySilentReleaseLock();
    }
}
