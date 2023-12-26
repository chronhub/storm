<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Projector\Subscription\Notification;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRised;

final readonly class RisePersistentProjection
{
    public function __construct(private MonitorRemoteStatus $monitor)
    {
    }

    public function __invoke(Notification $notification, callable $next): callable|bool
    {
        if ($notification->isRising()) {
            if ($this->monitor->shouldStop($notification->isInBackground())) {
                return false;
            }

            $notification->dispatch(new ProjectionRised());
        }

        return $next($notification);
    }
}
