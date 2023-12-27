<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\SleepWhenEmptyBatchStreams;

final readonly class SleepForQuery
{
    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        // checkMe
        $hub->listen(SleepWhenEmptyBatchStreams::class);

        return $next($hub);
    }
}
