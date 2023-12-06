<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Throwable;

final readonly class RunProjection
{
    public function __construct(
        private Subscription $subscription,
        private Workflow $workflow
    ) {
    }

    public function beginCycle(): void
    {
        try {
            do {
                $inProgress = $this->workflow->process(
                    static fn (Subscription $subscription): bool => $subscription->sprint()->inProgress()
                );
            } while ($this->subscription->sprint()->inBackground() && $inProgress);
        } catch (Throwable $exception) {
            $error = $exception;
        } finally {
            $this->tryReleaseLock($error ?? null);
        }
    }

    private function tryReleaseLock(?Throwable $exception): void
    {
        if (! $exception instanceof ProjectionAlreadyRunning && $this->subscription instanceof PersistentSubscriptionInterface) {
            try {
                $this->subscription->freed();
            } catch (Throwable) {
                // fail silently
            }
        }

        if ($exception instanceof Throwable) {
            throw $exception;
        }
    }
}
