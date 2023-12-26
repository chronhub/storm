<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Subscription\Notification;

final readonly class HandleStreamGap
{
    public function __construct(private PersistentManagement $management)
    {
    }

    public function __invoke(Notification $notification, callable $next): callable|bool
    {
        if ($notification->observeShouldSleepWhenGap() && ! $notification->IsEventReset()) {
            $this->management->store(); // todo dispatch event
        }

        return $next($notification);
    }
}
