<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification\Cycle;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class CycleStarted
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->loop()->start();
    }
}
