<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Throwable;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;

readonly class RunProjection
{
    public function __construct(
        private array $activities,
        protected ?ProjectionRepository $repository
    ) {
    }

    public function __invoke(Subscription $subscription): void
    {
        try {
            $quit = null;

            $this->beginCycle((new Workflow())->through($this->activities), $subscription);
        } catch (Throwable $exception) {
            $quit = $exception;
        } finally {
            $this->tryReleaseLock($quit);
        }
    }

    protected function beginCycle(Workflow $workflow, Subscription $subscription): void
    {
        do {
            $inProgress = $workflow
                ->send($subscription)
                ->then(static fn (Subscription $subscription): bool => $subscription->sprint()->inProgress());
        } while ($subscription->sprint()->inBackground() && $inProgress);
    }

    /**
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
