<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Scheme\Timer;

final class RunUntil
{
    private bool $started = false;

    private ?Timer $timer = null;

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        $interval = $subscription->context()->timer();

        if ($interval && ! $this->started) {
            $this->timer = new Timer($subscription->clock(), $interval);

            $this->timer->start();

            $this->started = true;
        }

        $response = $next($subscription);

        if ($this->started && $this->timer->isElapsed()) {
            $subscription->sprint()->stop();
        }

        return $response;
    }
}
