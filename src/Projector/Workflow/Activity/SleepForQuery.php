<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Support\NoEventStreamCounter;

final readonly class SleepForQuery
{
    public function __construct(private NoEventStreamCounter $noEventCounter)
    {
    }

    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        $this->noEventCounter->sleep();

        return $next($subscriptor);
    }
}
