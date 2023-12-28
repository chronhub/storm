<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionRise;
use Chronhub\Storm\Projector\Subscription\Notification\IsFirstLoop;

final readonly class RisePersistentProjection
{
    public function __construct(private MonitorRemoteStatus $monitor)
    {
    }

    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        if ($hub->interact(IsFirstLoop::class)) {
            if ($this->monitor->shouldStop($hub)) {
                return false;
            }

            $hub->trigger(new ProjectionRise());
        }

        return $next($hub);
    }
}
