<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final class RefreshProjection
{
    use RemoteStatusDiscovery;

    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        $this->discoverStatus($subscription);

        $queries = $subscription->context()->queries();

        $subscription->streamManager()->watchStreams($queries);

        return $next($subscription);
    }
}
