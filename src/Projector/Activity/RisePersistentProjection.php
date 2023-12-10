<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Subscription\Subscription;

final class RisePersistentProjection
{
    use MonitorRemoteStatus;

    protected Sprint $sprint;

    public function __construct(private readonly SubscriptionManagement $management)
    {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if ($this->isFirstCycle()) {
            $this->sprint = $subscription->sprint;

            // depending on the discovered status, the projection can be stopped early,
            // on stopping and on deleting.
            if ($this->shouldStopOnDiscoveringStatus()) {
                return false;
            }

            $this->management->rise();
        }

        return $next($subscription);
    }
}
