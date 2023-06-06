<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Scheme\Workflow;

final readonly class RunProjection
{
    private Workflow $workflow;

    private bool $keepRunning;

    public function __construct(
       Subscription $subscription,
       array $activities
    ) {
        $this->workflow = new Workflow($subscription);
        $this->workflow->through($activities);
        $this->keepRunning = $subscription->sprint()->inBackground();
    }

    public function beginCycle(): void
    {
        do {
            $inProgress = $this->workflow->process(
                static fn (Subscription $subscription): bool => $subscription->sprint()->inProgress()
            );
        } while ($this->keepRunning && $inProgress);
    }
}
