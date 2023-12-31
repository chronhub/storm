<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\EventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\IsFirstCycle;

final readonly class RiseQueryProjection
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        $hub->notifyWhen($hub->expect(IsFirstCycle::class), EventStreamDiscovered::class);

        return $next($hub);
    }
}
