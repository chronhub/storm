<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Cycle;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class CurrentCycle
{
    public function __invoke(Subscriptor $subscriptor): int
    {
        return $subscriptor->watcher()->loop()->cycle();
    }
}
