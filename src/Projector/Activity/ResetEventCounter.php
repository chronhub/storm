<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Subscription\Subscription;

final class ResetEventCounter
{
    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        $subscription->eventCounter->reset();

        return $next($subscription);
    }
}
