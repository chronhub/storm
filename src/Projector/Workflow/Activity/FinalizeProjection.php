<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Projector\Subscription\Notification;

final class FinalizeProjection
{
    public function __invoke(Notification $notification, callable $next): callable|bool
    {
        $notification->onResetAckedEvent();

        return $next($notification);
    }
}
