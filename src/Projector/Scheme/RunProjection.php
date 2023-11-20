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
            $this->handleException($exception);
        }

        $this->tryReleaseLock();
    }

    private function handleException(Throwable $exception): void
    {
        if (! $exception instanceof ProjectionAlreadyRunning) {
            $this->tryReleaseLock();
        }

        throw $exception;
    }

    private function tryReleaseLock(): void
    {
        if ($this->subscription instanceof PersistentSubscriptionInterface) {
            try {
                $this->subscription->freed();
            } catch (Throwable) {
                // todo logger
                // fail silently
            }
        }
    }
}
