<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Support\Notification\Cycle\IsFirstCycle;
use Chronhub\Storm\Projector\Support\Notification\Stream\EventStreamDiscovered;

final readonly class RiseQueryProjection
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        $hub->notifyWhen(
            $hub->expect(IsFirstCycle::class),
            fn (NotificationHub $hub) => $hub->notify(EventStreamDiscovered::class)
        );

        return $next($hub);
    }
}
