<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification\Checkpoint;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class CheckpointReset
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->recognition()->resets();
    }
}
