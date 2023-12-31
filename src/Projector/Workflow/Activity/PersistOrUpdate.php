<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionStored;
use Chronhub\Storm\Projector\Subscription\Notification\BatchSleep;
use Chronhub\Storm\Projector\Subscription\Notification\HasGap;
use Chronhub\Storm\Projector\Subscription\Notification\IsProcessBlank;

final readonly class PersistOrUpdate
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        // when no gap, we either update the lock, after sleeping, if we are running blank
        // or, we store the projection result
        if (! $hub->expect(HasGap::class)) {
            $hub->notifyWhen($hub->expect(IsProcessBlank::class), BatchSleep::class,
                fn (NotificationHub $hub) => $hub->trigger(new ProjectionLockUpdated()),
                fn (NotificationHub $hub) => $hub->trigger(new ProjectionStored()),
            );
        }

        return $next($hub);
    }
}
