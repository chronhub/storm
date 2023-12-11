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
        private bool $keepRunning,
        private ?PersistentManagement $management,
    ) {
    }

    public function beginCycle(): void
    {
        try {
            do {
                $inProgress = $this->workflow->process(
                    fn (Subscription $subscription): bool => $subscription->sprint->inProgress()
                );
            } while ($this->keepRunning && $inProgress);
        } catch (Throwable $exception) {
            $error = $exception;
        } finally {
            $this->tryReleaseLock($error ?? null);
        }
    }

    /**
     * @throws Throwable
     */
    private function tryReleaseLock(?Throwable $exception): void
    {
        if (! $exception instanceof ProjectionAlreadyRunning && $this->management) {
            try {
                $this->management->freed();
            } catch (Throwable) {
                // fail silently
            }
        }

        if ($exception instanceof Throwable) {
            throw $exception;
        }
    }
}
