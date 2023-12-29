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
        if ($hub->expect(HasGap::class)) {
            $hub->notify(ShouldSleepOnGap::class);

            if (! $hub->expect(IsEventCounterReset::class)) {
                $hub->trigger(new ProjectionStored());
            }
        }

        return $next($hub);
    }
}
