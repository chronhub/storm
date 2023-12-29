<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;
use Chronhub\Storm\Projector\Workflow\Timer;

final readonly class RunUntil
{
    public function __construct(private Timer $timer)
    {
    }

    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        if (! $this->timer->isStarted()) {
            $this->timer->start();
        }

        $response = $next($hub);

        if ($this->timer->isExpired()) {
            $hub->notify(SprintStopped::class);

            return false;
        }

        return $response;
    }
}
