<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Action;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\GapDetected;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Notification\StopWhenGapDetected;

final class WhenGapDetected
{
    public function __invoke(NotificationHub $hub, GapDetected $capture): void
    {
        if ($hub->expect(StopWhenGapDetected::class)) {
            $hub->notify(SprintStopped::class);
        }
    }
}
