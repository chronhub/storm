<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

class StopWhenMasterCounterIsReached
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        $current = $subscriptor->watcher()->masterCounter()->current();

        return $subscriptor->watcher()->stopWhen()->masterCounterReach($current);
    }
}
