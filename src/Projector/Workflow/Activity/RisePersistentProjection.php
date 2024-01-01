<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionRise;
use Chronhub\Storm\Projector\Subscription\Notification\Cycle\IsFirstCycle;

final class RisePersistentProjection
{
    use MonitorRemoteStatus;

    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        if ($hub->expect(IsFirstCycle::class)) {
            if ($this->shouldStop($hub)) {
                return false;
            }

            $hub->trigger(new ProjectionRise());
        }

        return $next($hub);
    }
}
