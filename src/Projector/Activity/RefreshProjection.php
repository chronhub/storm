<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final class RefreshProjection
{
    use RemoteStatusDiscovery;

    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        // checkMe use shared class instead of trait
        $this->disableFlag();

        /**
         * Depending on the discovered status, the projection
         * can be stopped, restarted if in the background or just keep going
         */
        $this->discoverStatus($subscription);

        /**
         * Watch again for event streams which may have
         * been added or deleted after the first watch.
         */
        $queries = $subscription->context()->queries();
        $subscription->streamManager()->watchStreams($queries);

        return $next($subscription);
    }
}
