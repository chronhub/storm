<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\EventCounterReset;

final readonly class ResetEventCounter
{
    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        $hub->interact(EventCounterReset::class);

        return $next($hub);
    }
}
