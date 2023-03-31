<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscription;

final class ResetEventCounter
{
    public function __invoke(PersistentSubscription $subscription, callable $next): callable|bool
    {
        $subscription->eventCounter()->reset();

        return $next($subscription);
    }
}
