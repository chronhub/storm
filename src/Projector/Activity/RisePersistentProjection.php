<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final class RisePersistentProjection
{
    use MonitorRemoteStatus;

    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        if ($this->isFirstCycle()) {
            // Depending on the discovered status, the projection can be stopped early,
            // on stopping and on deleting.
            if ($this->shouldStopOnDiscoveringStatus($subscription)) {
                return false;
            }

            $subscription->rise();
        }

        return $next($subscription);
    }
}
