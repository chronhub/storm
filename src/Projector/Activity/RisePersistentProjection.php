<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Subscription\Subscription;

final readonly class RisePersistentProjection
{
    public function __construct(
        private MonitorRemoteStatus $monitor,
        private PersistentManagement $management
    ) {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if ($subscription->looper->isFirstLap()) {
            // depending on the discovered status,
            // the projection can be stopped early, on stopping and on deleting.
            if ($this->monitor->shouldStop($this->management, $subscription->sprint)) {
                return false;
            }

            $this->management->rise();
        }

        return $next($subscription);
    }
}
