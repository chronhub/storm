<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final class RisePersistentProjection
{
    use RemoteStatusDiscovery;

    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        if ($this->isFirstExecution()) {
            if ($this->shouldStopOnDiscoverStatus($subscription)) {
                return false;
            }

            $subscription->rise();

            $this->disableFlag();
        }

        return $next($subscription);
    }
}
