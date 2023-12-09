<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Subscription\Beacon;

final class RisePersistentProjection
{
    use MonitorRemoteStatus;

    protected Sprint $sprint;

    public function __construct(private readonly PersistentSubscriber $subscription)
    {
    }

    public function __invoke(Beacon $manager, callable $next): callable|bool
    {
        if ($this->isFirstCycle()) {
            $this->sprint = $manager->sprint;

            // depending on the discovered status, the projection can be stopped early,
            // on stopping and on deleting.
            if ($this->shouldStopOnDiscoveringStatus()) {
                return false;
            }

            $this->subscription->rise();
        }

        return $next($manager);
    }
}
