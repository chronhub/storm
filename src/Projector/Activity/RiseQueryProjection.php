<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\QuerySubscriptionInterface;

final class RiseQueryProjection
{
    private bool $isFirstCycle = true;

    public function __invoke(QuerySubscriptionInterface $subscription, callable $next): callable|bool
    {
        if ($this->isFirstCycle) {
            $this->isFirstCycle = false;

            $queries = $subscription->context()->queries();

            $subscription->streamManager()->discover($queries);
        }

        return $next($subscription);
    }
}
