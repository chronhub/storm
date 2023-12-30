<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Action;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintTerminated;
use Chronhub\Storm\Projector\Subscription\Notification\MasterCounterReset;
use Chronhub\Storm\Projector\Subscription\Notification\TimeReset;

final class WhenExpectSprintTermination
{
    // todo change action for subscriber
    public function __invoke(NotificationHub $hub, IsSprintTerminated $capture, bool $shouldStop): void
    {
        if ($shouldStop) {
            $hub->notify(TimeReset::class);
            $hub->notify(MasterCounterReset::class);
        }
    }
}
