<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Scheme\Timer;
use Chronhub\Storm\Projector\Subscription\Subscription;

final class RunUntil
{
    private ?Timer $timer = null;

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        $interval = $subscription->context()->timer();

        if ($interval && $this->timer === null) {
            $this->timer = new Timer($subscription->clock, $interval);

            $this->timer->start();
        }

        $response = $next($subscription);

        if ($this->timer && $this->timer->isElapsed()) {
            $subscription->sprint->stop(); // checkMe

            return false;
        }

        return $response;
    }
}
