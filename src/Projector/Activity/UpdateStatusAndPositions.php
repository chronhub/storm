<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;

final class UpdateStatusAndPositions
{
    use RemoteStatusDiscovery;

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        $this->recoverProjectionStatus(false, $subscription->sprint()->inBackground());

        $queries = $subscription->context()->queries();

        $subscription->streamPosition()->watch($queries);

        return $next($subscription);
    }
}
