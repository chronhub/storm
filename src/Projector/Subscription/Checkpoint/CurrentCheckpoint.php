<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Checkpoint;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Stream\Checkpoint;

final class CurrentCheckpoint
{
    /**
     * @return array<string, Checkpoint>
     */
    public function __invoke(Subscriptor $subscriptor): array
    {
        return $subscriptor->recognition()->checkpoints();
    }
}
