<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\EventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintRunning;

final class RefreshProjection
{
    use MonitorRemoteStatus;

    public function __construct(private readonly bool $onlyOnceDiscovery)
    {
    }

    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        // monitor update in the remote status
        $this->refreshStatus($hub);

        // watch again for event streams which may have changed after the first discovery
        if ($this->shouldDiscoverAgain($hub)) {
            $hub->notify(EventStreamDiscovered::class);
        }

        return $next($hub);
    }

    /**
     * Prevent discovering again event streams
     * when only once discovery or sprint is stopped.
     *
     * checkMe: do we need to discover again when sprint stops?
     */
    private function shouldDiscoverAgain(HookHub $hub): bool
    {
        if ($this->onlyOnceDiscovery) {
            return false;
        }

        return $hub->expect(IsSprintRunning::class);
    }
}
