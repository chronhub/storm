<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Action;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\ExpectSprintTermination;
use Chronhub\Storm\Projector\Subscription\Notification\MasterEventCounterReset;
use Chronhub\Storm\Projector\Subscription\Notification\TimeReset;

final class WhenExpectSprintTermination
{
    public function __invoke(NotificationHub $hub, ExpectSprintTermination $capture, bool $shouldStop): void
    {
        if ($shouldStop) {
            $hub->notify(TimeReset::class);
            $hub->notify(MasterEventCounterReset::class);
        }
    }
}
