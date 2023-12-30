<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionRise;
use Chronhub\Storm\Projector\Subscription\Notification\IsFirstLoop;

final class RisePersistentProjection
{
    use MonitorRemoteStatus;

    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        if ($hub->expect(IsFirstLoop::class)) {
            if ($this->shouldStop($hub)) {
                return false;
            }

            $hub->trigger(new ProjectionRise());
        }

        return $next($hub);
    }
}
