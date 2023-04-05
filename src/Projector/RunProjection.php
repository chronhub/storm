<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Throwable;

final readonly class RunProjection
{
    public function __construct(
        private array $activities,
        protected ?ProjectionManagement $repository
    ) {
    }

    public function __invoke(Subscription $subscription): void
    {
        try {
            $quit = null;

            $this->beginCycle(
                $this->newWorkflow($subscription), $subscription->sprint()->inBackground()
            );
        } catch (Throwable $exception) {
            $quit = $exception;
        } finally {
            $this->tryReleaseLock($quit);
        }
    }

    protected function beginCycle(Workflow $workflow, bool $keepRunning): void
    {
        do {
            $inProgress = $workflow->process(
                static fn (Subscription $subscription): bool => $subscription->sprint()->inProgress()
            );
        } while ($keepRunning && $inProgress);
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

    protected function newWorkflow(Subscription $subscription): Workflow
    {
        $stub = new Workflow($subscription);

        return $stub->through($this->activities);
    }
}
