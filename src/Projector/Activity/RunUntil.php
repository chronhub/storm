<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Scheme\Timer;
use Chronhub\Storm\Projector\Subscription\Beacon;

final class RunUntil
{
    private bool $started = false;

    private ?Timer $timer = null;

    public function __invoke(Beacon $manager, callable $next): callable|bool
    {
        $interval = $manager->context()->timer();

        if ($interval && ! $this->started) {
            $this->timer = new Timer($manager->clock, $interval);

            $this->timer->start();

            $this->started = true;
        }

        $response = $next($manager);

        if ($this->started && $this->timer->isElapsed()) {
            $manager->sprint->stop();
        }

        return $response;
    }
}
