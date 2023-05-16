<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Scheme\Timer;
use Chronhub\Storm\Projector\Scheme\Workflow;

final readonly class RunProjection
{
    public function __construct(private array $activities)
    {
    }

    public function __invoke(Subscription $subscription): void
    {
         $this->beginCycle(
            $this->newWorkflow($subscription),
            $subscription->sprint()->inBackground(),
            $this->createTimer($subscription)
         );
    }

    private function beginCycle(Workflow $workflow, bool $keepRunning, Timer $timer): void
    {
        do {
            $timer->start();

            $inProgress = $workflow->process(
                static fn (Subscription $subscription): bool => $subscription->sprint()->inProgress()
            );
        } while ($keepRunning && $inProgress && ! $timer->isNotElapsed());
    }

    private function newWorkflow(Subscription $subscription): Workflow
    {
        $stub = new Workflow($subscription);

        return $stub->through($this->activities);
    }

    private function createTimer(Subscription $subscription): Timer
    {
        return new Timer($subscription->clock(), $subscription->context()->timer());
    }
}
