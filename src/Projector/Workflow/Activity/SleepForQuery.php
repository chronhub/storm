<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Projector\Subscription\Notification;

final readonly class SleepForQuery
{
    public function __construct()
    {
    }

    public function __invoke(Notification $notification, callable $next): callable|bool
    {
        // checkMe
        $notification->onSleepWhenEmptyBatchStreams();

        return $next($notification);
    }
}
