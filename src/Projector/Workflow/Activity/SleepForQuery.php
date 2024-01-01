<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Batch\BatchSleep;

final readonly class SleepForQuery
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        // checkMe
        $hub->notify(BatchSleep::class);

        return $next($hub);
    }
}
