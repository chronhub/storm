<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\StreamsDiscovered;

final readonly class RefreshProjection
{
    public function __construct(private MonitorRemoteStatus $monitor)
    {
    }

    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        $this->monitor->refreshStatus($hub);

        // watch again for event streams which may have changed after the first watch.
        $hub->listen(StreamsDiscovered::class);

        return $next($hub);
    }
}
