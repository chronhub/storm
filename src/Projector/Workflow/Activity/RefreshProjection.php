<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\StreamsDiscovered;

final class RefreshProjection
{
    use MonitorRemoteStatus;

    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        $this->refreshStatus($hub);

        // watch again for event streams which may have changed after the first watch.
        $hub->notify(StreamsDiscovered::class);

        return $next($hub);
    }
}
