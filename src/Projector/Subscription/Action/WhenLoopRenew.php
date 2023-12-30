<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Action;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\EventCounterReset;
use Chronhub\Storm\Projector\Subscription\Notification\LoopRenew;
use Chronhub\Storm\Projector\Subscription\Notification\StreamEventAckedReset;

final class WhenLoopRenew
{
    public function __invoke(NotificationHub $hub, LoopRenew $capture): void
    {
        $hub->notify(EventCounterReset::class);

        $hub->notify(StreamEventAckedReset::class);
    }
}
