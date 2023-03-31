<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Throwable;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;

readonly class RunProjection
{
    public function __construct(
        private array $activities,
        private ?SubscriptionManagement $repository
    ) {
    }

    public function __invoke(Subscription $subscription): void
    {
        $workflow = Workflow::carry($subscription, $this->activities);

        try {
            $quit = null;
            $this->runProjection($workflow, $subscription);
        } catch (Throwable $exception) {
            $quit = $exception;
        } finally {
            $this->tryReleaseLock($quit);
        }
    }

    protected function runProjection(Workflow $workflow, Subscription $subscription): void
    {
        do {
            $isStopped = $workflow->then(static fn (Subscription $subscription): bool => $subscription->runner->isStopped());
        } while ($subscription->runner->inBackground() && ! $isStopped);
    }

    /**
     * Try release lock
     *
     * if an error occurred releasing lock, we just failed silently
     * and raise the original exception if exists
     *
     * @throws ProjectionAlreadyRunning
     * @throws Throwable
     */
    protected function tryReleaseLock(?Throwable $exception): void
    {
        if ($exception instanceof ProjectionAlreadyRunning) {
            throw $exception;
        }

        try {
            $this->repository?->freed();
        } catch (Throwable) {
            // failed silently
        }

        if ($exception) {
            throw $exception;
        }
    }
}
