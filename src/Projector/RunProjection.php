<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Throwable;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;

final readonly class RunProjection
{
    public function __construct(
        private array $activities,
        protected ?ProjectionRepositoryInterface $repository
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
                ->process($subscription)
                ->then(static fn (Subscription $subscription): bool => $subscription->sprint()->inProgress());
        } while ($subscription->sprint()->inBackground() && $inProgress);
    }

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
