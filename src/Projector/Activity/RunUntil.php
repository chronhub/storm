<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Scheme\Timer;
use Closure;

final class RunUntil
{
    private bool $started = false;

    private Timer $timer;

    public function __invoke(Subscription $subscription, Closure $next): Closure|bool
    {
        $timer = $subscription->context()->timer();

        if ($timer && ! $this->started) {
            $this->timer = new Timer($subscription->clock(), $timer);

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
