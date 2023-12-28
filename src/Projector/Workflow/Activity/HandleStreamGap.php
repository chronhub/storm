<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionStored;
use Chronhub\Storm\Projector\Subscription\Notification\HasGap;
use Chronhub\Storm\Projector\Subscription\Notification\IsEventCounterReset;
use Chronhub\Storm\Projector\Subscription\Notification\ShouldSleepOnGap;

final class HandleStreamGap
{
    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        if ($hub->interact(HasGap::class)) {
            $hub->interact(ShouldSleepOnGap::class);

            if (! $hub->interact(IsEventCounterReset::class)) {
                $hub->trigger(new ProjectionStored());
            }
        }

        return $next($hub);
    }
}
