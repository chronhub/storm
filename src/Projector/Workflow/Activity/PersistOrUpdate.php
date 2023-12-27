<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\BatchObserverSleep;
use Chronhub\Storm\Projector\Subscription\Notification\HasGap;
use Chronhub\Storm\Projector\Subscription\Notification\IsEventReset;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStored;

final readonly class PersistOrUpdate
{
    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        if (! $hub->interact(HasGap::class)) {
            $hook = $this->getHook($hub);

            $hub->trigger($hook);
        }

        return $next($hub);
    }

    private function getHook(HookHub $hub): object
    {
        if ($hub->interact(IsEventReset::class)) {
            $hub->interact(BatchObserverSleep::class);

            return new ProjectionLockUpdated();
        }

        return new ProjectionStored();
    }
}
