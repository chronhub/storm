<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\IsTimeExpired;
use Chronhub\Storm\Projector\Subscription\Notification\IsTimeStarted;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Notification\TimeStarted;

final readonly class RunUntil
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        if (! $hub->expect(IsTimeStarted::class)) {
            $hub->notify(TimeStarted::class);
        }

        $response = $next($hub);

        if ($hub->expect(IsTimeExpired::class)) {
            $hub->notify(SprintStopped::class);

            return false;
        }

        return $response;
    }
}
