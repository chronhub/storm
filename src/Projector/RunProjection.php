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
        $timer = new Timer($subscription->clock(), $subscription->context()->timer());

         $this->beginCycle(
            $this->newWorkflow($subscription, $timer),
            $subscription->sprint()->inBackground(),
             $timer
         );
    }

    private function beginCycle(Workflow $workflow, bool $keepRunning, Timer $timer): void
    {
        $timer->start();

        do {
            $inProgress = $workflow->process(
                static fn (Subscription $subscription): bool => $subscription->sprint()->inProgress()
            );
        } while ($keepRunning && $inProgress && ! $timer->isElapsed());
    }

    private function newWorkflow(Subscription $subscription, Timer $timer): Workflow
    {
        $stub = new Workflow($subscription, $timer);

        return $stub->through($this->activities);
    }
}
