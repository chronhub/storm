<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Subscription\Notification;

final readonly class PersistOrUpdate
{
    public function __construct(private PersistentManagement $management)
    {
    }

    public function __invoke(Notification $notification, callable $next): callable|bool
    {
        if (! $notification->hasGap()) {
            $notification->isEventReset() ? $this->management->tryUpdateLock() : $this->management->store();
        }

        return $next($notification);
    }
}
