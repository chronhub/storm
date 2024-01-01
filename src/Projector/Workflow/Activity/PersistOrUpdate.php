<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Batch\BatchSleep;
use Chronhub\Storm\Projector\Subscription\Batch\IsProcessBlank;
use Chronhub\Storm\Projector\Subscription\Checkpoint\HasGap;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionStored;
use Chronhub\Storm\Projector\Subscription\Sprint\IsSprintTerminated;

final readonly class PersistOrUpdate
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        if ($hub->expect(HasGap::class)) {
            return $next($hub);
        }

        // when no gap, we either update the lock, after sleeping, if we are running blank
        // or, we store the projection result with the last processed events
        $hub->notifyWhen(
            $hub->expect(IsProcessBlank::class),
            function (NotificationHub $hub) {
                $hub->notifyWhen(
                    $hub->expect(IsSprintTerminated::class),
                    fn (NotificationHub $hub) => $hub->notify(BatchSleep::class)
                );

                $hub->trigger(new ProjectionLockUpdated());
            },
            fn (NotificationHub $hub) => $hub->trigger(new ProjectionStored()),
        );

        return $next($hub);
    }
}
