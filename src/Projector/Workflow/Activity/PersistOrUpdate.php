<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Projector\Subscription\Notification;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStored;

final readonly class PersistOrUpdate
{
    public function __invoke(Notification $notification, callable $next): callable|bool
    {
        if (! $notification->hasGap()) {
            $notification->isEventReset()
                ? $notification->dispatch(new ProjectionLockUpdated())
                : $notification->dispatch(new ProjectionStored());
        }

        return $next($notification);
    }
}
