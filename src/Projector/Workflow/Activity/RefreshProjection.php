<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Subscription\Notification\StreamsDiscovered;

final readonly class RefreshProjection
{
    public function __construct(
        private MonitorRemoteStatus $monitor,
        private PersistentManagement $management
    ) {
    }

    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        // depending on the discovered status, the projection
        // can be stopped, restarted if in the background or just keep going.
        $this->monitor->refreshStatus($this->management, $subscriptor->inBackground());

        // watch again for event streams which may have changed after the first watch.
        $subscriptor->receive(new StreamsDiscovered());

        return $next($subscriptor);
    }
}
