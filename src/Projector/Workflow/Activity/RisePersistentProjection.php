<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\IsRising;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRise;

final readonly class RisePersistentProjection
{
    public function __construct(private MonitorRemoteStatus $monitor)
    {
    }

    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        if ($hub->listen(IsRising::class)) {
            if ($this->monitor->shouldStop($hub)) {
                return false;
            }

            $hub->trigger(new ProjectionRise());
        }

        return $next($hub);
    }
}
