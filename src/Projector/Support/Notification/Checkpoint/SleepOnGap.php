<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Checkpoint;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class SleepOnGap
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->recognition()->sleepWhenGap();
    }
}