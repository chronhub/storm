<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Subscription\Subscription;

final readonly class RefreshProjection
{
    public function __construct(
        private MonitorRemoteStatus $discovering,
        private PersistentManagement $management
    ) {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        // depending on the discovered status, the projection
        // can be stopped, restarted if in the background or just keep going.
        $this->discovering->refreshStatus($this->management, $subscription->sprint);

        // watch again for event streams which may have
        // changed after the first watch.
        $subscription->discoverStreams();

        return $next($subscription);
    }
}
