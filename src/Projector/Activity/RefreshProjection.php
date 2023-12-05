<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final class RefreshProjection
{
    use MonitorRemoteStatus;

    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        /**
         * Depending on the discovered status, the projection
         * can be stopped, restarted if in the background or just keep going
         */
        $this->refreshStatus($subscription);

        /**
         * Watch again for event streams which may have
         * changed after the first watch.
         */
        $queries = $subscription->context()->queries();
        $subscription->streamManager()->discover($queries);

        return $next($subscription);
    }
}
