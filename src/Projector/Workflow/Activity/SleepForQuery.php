<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Subscription\Notification\SleepWhenEmptyBatchStreams;

final readonly class SleepForQuery
{
    public function __construct()
    {
    }

    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        // checkMe
        $subscriptor->receive(new SleepWhenEmptyBatchStreams());

        return $next($subscriptor);
    }
}
