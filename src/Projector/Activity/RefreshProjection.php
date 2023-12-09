<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Subscription\Beacon;

final class RefreshProjection
{
    use MonitorRemoteStatus;

    protected Sprint $sprint;

    public function __construct(protected PersistentSubscriber $subscription)
    {
    }

    public function __invoke(Beacon $manager, callable $next): callable|bool
    {
        $this->sprint = $manager->sprint; // todo

        // depending on the discovered status, the projection
        // can be stopped, restarted if in the background or just keep going.
        $this->refreshStatus();

        // watch again for event streams which may have
        // changed after the first watch.
        // todo encapsulate in a method
        $queries = $manager->context()->queries();
        $manager->streamBinder->discover($queries);

        return $next($manager);
    }
}
