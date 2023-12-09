<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\Subscriber;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Throwable;

final readonly class RunProjection
{
    public function __construct(
        private Workflow $workflow,
        private bool $keepRunning,
        private ?SubscriptionManagement $subscription,
    ) {
    }

    public function beginCycle(): void
    {
        try {
            do {
                $inProgress = $this->workflow->process(
                    fn (Subscriber $subscriber): bool => $subscriber->sprint->inProgress()
                );
            } while ($this->keepRunning && $inProgress);
        } catch (Throwable $exception) {
            $error = $exception;
        } finally {
            $this->tryReleaseLock($error ?? null);
        }
    }

    private function tryReleaseLock(?Throwable $exception): void
    {
        if (! $exception instanceof ProjectionAlreadyRunning && $this->subscription) {
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
