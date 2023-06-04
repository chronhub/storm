<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Scheme\Workflow;

final readonly class RunProjection
{
    public function __invoke(Subscription $subscription, array $activities): void
    {
        $workflow = new Workflow($subscription);
        $workflow->through($activities);

         $this->beginCycle($workflow, $subscription->sprint()->inBackground());
    }

    private function beginCycle(Workflow $workflow, bool $keepRunning): void
    {
        do {
            $inProgress = $workflow->process(
                static fn (Subscription $subscription): bool => $subscription->sprint()->inProgress()
            );
        } while ($keepRunning && $inProgress);
    }
}
