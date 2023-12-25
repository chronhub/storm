<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Support\Timer;

final readonly class RunUntil
{
    public function __construct(private Timer $timer)
    {
    }

    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        if (! $this->timer->isStarted()) {
            $this->timer->start();
        }

        $response = $next($subscriptor);

        if ($this->timer->isExpired()) {
            $subscriptor->stop();

            return false;
        }

        return $response;
    }
}
