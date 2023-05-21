<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final class RefreshProjection
{
    use RemoteStatusDiscovery;

    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        $this->discloseProjectionStatus($subscription);

        $queries = $subscription->context()->queries();

        $subscription->streamPosition()->watch($queries);

        return $next($subscription);
    }

    public function isFirstExecution(): false
    {
        return false;
    }
}
