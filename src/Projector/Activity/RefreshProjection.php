<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use Chronhub\Storm\Projector\Scheme\Sprint;

final class RefreshProjection
{
    use MonitorRemoteStatus;

    protected Sprint $sprint;

    public function __construct(protected SubscriptionManagement $subscription)
    {
    }

    public function __invoke(PersistentSubscriber $subscriber, callable $next): callable|bool
    {
        $this->sprint = $subscriber->sprint; // todo

        // depending on the discovered status, the projection
        // can be stopped, restarted if in the background or just keep going.
        $this->refreshStatus();

        // watch again for event streams which may have
        // changed after the first watch.
        // todo encapsulate in a method
        $queries = $subscriber->context()->queries();
        $subscriber->streamBinder->discover($queries);

        return $next($subscriber);
    }
}
