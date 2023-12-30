<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\EventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\IsFirstLoop;

final readonly class RiseQueryProjection
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        if ($hub->expect(IsFirstLoop::class)) {
            $hub->notify(EventStreamDiscovered::class);
        }

        return $next($hub);
    }
}
