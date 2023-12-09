<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriber;
use Chronhub\Storm\Projector\Scheme\Timer;

final class RunUntil
{
    private bool $started = false;

    private ?Timer $timer = null;

    public function __invoke(Subscriber $subscriber, callable $next): callable|bool
    {
        $interval = $subscriber->context()->timer();

        if ($interval && ! $this->started) {
            $this->timer = new Timer($subscriber->clock, $interval);

            $this->timer->start();

            $this->started = true;
        }

        $response = $next($subscriber);

        if ($this->started && $this->timer->isElapsed()) {
            $subscriber->sprint->stop();
        }

        return $response;
    }
}
