<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final class RefreshProjection
{
    use RemoteStatusDiscovery;

    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        $this->subscription ??= $subscription;

        $this->discloseProjectionStatus(false, $subscription->sprint()->inBackground());

        $queries = $subscription->context()->queries();

        $subscription->streamPosition()->watch($queries);

        return $next($subscription);
    }
}
