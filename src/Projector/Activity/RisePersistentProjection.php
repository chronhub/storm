<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final class RisePersistentProjection
{
    use RemoteStatusDiscovery;

    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        if ($this->isFirstCycle()) {
            /**
             * Depending on the discovered status of the projection, we will
             * have to stop the projection early.
             */
            if ($this->shouldStopOnDiscoveringStatus($subscription)) {
                return false;
            }

            $subscription->rise();
        }

        return $next($subscription);
    }
}
