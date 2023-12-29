<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionStored;
use Chronhub\Storm\Projector\Subscription\Notification\BatchSleep;
use Chronhub\Storm\Projector\Subscription\Notification\HasGap;
use Chronhub\Storm\Projector\Subscription\Notification\HasStreamEventAcked;
use Chronhub\Storm\Projector\Subscription\Notification\IsEventCounterReset;

final readonly class PersistOrUpdate
{
    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        if (! $hub->expect(HasGap::class)) {
            $hook = $this->getHook($hub);

            $hub->trigger($hook);
        }

        return $next($hub);
    }

    private function getHook(HookHub $hub): object
    {
        if ($hub->expect(IsEventCounterReset::class)) {
            // we only sleep when the counter has been reset and no event has been processed,
            if (! $hub->expect(HasStreamEventAcked::class)) {
                $hub->notify(BatchSleep::class);
            }

            return new ProjectionLockUpdated();
        }

        return new ProjectionStored();
    }
}
