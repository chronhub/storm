<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Projector\Subscription\Notification;
use Chronhub\Storm\Projector\Support\Timer;

final readonly class RunUntil
{
    public function __construct(private Timer $timer)
    {
    }

    public function __invoke(Notification $notification, callable $next): callable|bool
    {
        if (! $this->timer->isStarted()) {
            $this->timer->start();
        }

        $response = $next($notification);

        if ($this->timer->isExpired()) {
            $notification->onProjectionStopped();

            return false;
        }

        return $response;
    }
}
