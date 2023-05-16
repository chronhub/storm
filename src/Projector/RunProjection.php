<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\Subscription;
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
            $subscription->sprint()->inBackground()
         );
    }

    private function beginCycle(Workflow $workflow, bool $keepRunning): void
    {
        do {
            $inProgress = $workflow->process(
                static fn (Subscription $subscription): bool => $subscription->sprint()->inProgress()
            );
        } while ($keepRunning && $inProgress);
    }

    private function newWorkflow(Subscription $subscription): Workflow
    {
        $stub = new Workflow($subscription);

        return $stub->through($this->activities);
    }
}
