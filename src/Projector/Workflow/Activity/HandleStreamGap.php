<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\IsEventReset;
use Chronhub\Storm\Projector\Subscription\Notification\ShouldSleepOnGap;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStored;

final class HandleStreamGap
{
    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        if ($hub->interact(ShouldSleepOnGap::class) && ! $hub->interact(IsEventReset::class)) {
            $hub->trigger(new ProjectionStored());
        }

        return $next($hub);
    }
}
