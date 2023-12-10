<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Subscription\Subscription;

final class RefreshProjection
{
    use MonitorRemoteStatus;

    protected Sprint $sprint;

    public function __construct(protected SubscriptionManagement $management)
    {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        $this->sprint = $subscription->sprint; // todo

        // depending on the discovered status, the projection
        // can be stopped, restarted if in the background or just keep going.
        $this->refreshStatus();

        // watch again for event streams which may have
        // changed after the first watch.
        $subscription->discoverStreams();

        return $next($subscription);
    }
}
