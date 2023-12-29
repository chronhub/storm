<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintRunning;

final readonly class RunProjection
{
    public function __construct(private Workflow $workflow, private bool $keepRunning)
    {
    }

    public function loop(): void
    {
        do {
            $inProgress = $this->workflow->process(
                fn (HookHub $hub): bool => $hub->expect(IsSprintRunning::class)
            );
        } while ($this->keepRunning && $inProgress);
    }
}
