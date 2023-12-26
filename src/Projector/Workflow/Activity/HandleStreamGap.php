<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Projector\Subscription\Notification;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStored;

final class HandleStreamGap
{
    public function __invoke(Notification $notification, callable $next): callable|bool
    {
        if ($notification->observeShouldSleepWhenGap() && ! $notification->IsEventReset()) {
            $notification->dispatch(new ProjectionStored());
        }

        return $next($notification);
    }
}
