<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Batch\IsBatchCounterReset;
use Chronhub\Storm\Projector\Subscription\Checkpoint\HasGap;
use Chronhub\Storm\Projector\Subscription\Checkpoint\SleepOnGap;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionStored;

final class HandleStreamGap
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        // when a gap is detected, we first, sleep for a while,
        // to let the remote storage to fix it, and then
        // we store the projection result if some stream events
        // have been processed before the gap detection.
        $hub->notifyWhen(
            $hub->expect(HasGap::class),
            function (NotificationHub $hub): void {
                // sleep and decrement retries left
                $hub->notify(SleepOnGap::class);

                if (! $hub->expect(IsBatchCounterReset::class)) {
                    $hub->trigger(new ProjectionStored());
                }
            });

        return $next($hub);
    }
}
