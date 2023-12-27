<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\HasGap;
use Chronhub\Storm\Projector\Subscription\Notification\IsEventReset;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStored;

final readonly class PersistOrUpdate
{
    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        if (! $hub->listen(HasGap::class)) {
            $dispatchEvent = $hub->listen(IsEventReset::class)
                ? new ProjectionLockUpdated() : new ProjectionStored();

            $hub->trigger($dispatchEvent);
        }

        return $next($hub);
    }
}
