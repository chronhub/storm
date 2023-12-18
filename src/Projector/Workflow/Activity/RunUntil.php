<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Projector\Support\Timer;

final readonly class RunUntil
{
    public function __construct(private Timer $timer)
    {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if (! $this->timer->isStarted()) {
            $this->timer->start();
        }

        $response = $next($subscription);

        if ($this->timer->isExpired()) {
            $subscription->sprint->stop();

            return false;
        }

        return $response;
    }
}
