<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionStored;
use Chronhub\Storm\Projector\Subscription\Notification\BatchSleep;
use Chronhub\Storm\Projector\Subscription\Notification\HasGap;
use Chronhub\Storm\Projector\Subscription\Notification\ProcessBlank;

final readonly class PersistOrUpdate
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        // when no gap, we either update the lock if we are running blank
        // or we store the projection result
        if (! $hub->expect(HasGap::class)) {
            $hook = $this->getHook($hub);

            $hub->trigger($hook);
        }

        return $next($hub);
    }

    private function getHook(NotificationHub $hub): object
    {
        if ($hub->expect(ProcessBlank::class)) {
            $hub->notify(BatchSleep::class);

            return new ProjectionLockUpdated();
        }

        return new ProjectionStored();
    }
}
