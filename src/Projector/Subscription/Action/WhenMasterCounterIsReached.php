<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Action;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\EventCounterIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Notification\StopWhenMasterCounterIsReached;

final class WhenMasterCounterIsReached
{
    public function __invoke(NotificationHub $hub, EventCounterIncremented $capture): void
    {
        if ($hub->expect(StopWhenMasterCounterIsReached::class)) {
            $hub->notify(SprintStopped::class);
        }
    }
}
