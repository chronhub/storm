<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Projector\Subscription\Notification;

final readonly class RiseQueryProjection
{
    public function __invoke(Notification $notification, callable $next): callable|bool
    {
        if ($notification->isRising()) {
            $notification->onStreamsDiscovered();
        }

        return $next($notification);
    }
}
