<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionStored;
use Chronhub\Storm\Projector\Subscription\Notification\Batch\IsBatchCounterReset;
use Chronhub\Storm\Projector\Subscription\Notification\Checkpoint\HasGap;
use Chronhub\Storm\Projector\Subscription\Notification\Checkpoint\SleepOnGap;

final class HandleStreamGap
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        $hub->notifyWhen(
            $hub->expect(HasGap::class),
            function (NotificationHub $hub): void {
                $hub->notify(SleepOnGap::class);

                if (! $hub->expect(IsBatchCounterReset::class)) {
                    $hub->trigger(new ProjectionStored());
                }
            });

        return $next($hub);
    }
}
