<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Projector\Subscription\Notification;

final readonly class RefreshProjection
{
    public function __construct(private MonitorRemoteStatus $monitor)
    {
    }

    public function __invoke(Notification $notification, callable $next): callable|bool
    {
        $this->monitor->refreshStatus($notification);

        // watch again for event streams which may have changed after the first watch.
        $notification->onStreamsDiscovered();

        return $next($notification);
    }
}
