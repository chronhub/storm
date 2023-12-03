<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final class RefreshProjection
{
    use RemoteStatusDiscovery;

    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        /**
         * Depending on the discovered status,
         * the projection can be stopped, restarted or just keep going
         */
        $this->discoverStatus($subscription);

        // checkMe
        $queries = $subscription->context()->queries();
        $subscription->streamManager()->watchStreams($queries);

        return $next($subscription);
    }
}
