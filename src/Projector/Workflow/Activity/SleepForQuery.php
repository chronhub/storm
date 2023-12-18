<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Projector\Support\NoStreamLoadedCounter;

final readonly class SleepForQuery
{
    public function __construct(private NoStreamLoadedCounter $eventCounter)
    {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        $this->eventCounter->sleep();

        return $next($subscription);
    }
}
