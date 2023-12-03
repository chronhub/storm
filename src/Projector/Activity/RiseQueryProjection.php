<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;

final class RiseQueryProjection
{
    private bool $isFirstCycle = true;

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if ($this->isFirstCycle) {
            $this->isFirstCycle = false;

            $queries = $subscription->context()->queries();

            $subscription->streamManager()->discover($queries);
        }

        return $next($subscription);
    }
}
