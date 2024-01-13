<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Checkpoint;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Checkpoint\Checkpoint;

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
