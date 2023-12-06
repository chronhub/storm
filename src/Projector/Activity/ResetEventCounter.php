<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final class ResetEventCounter
{
    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        $subscription->eventCounter()->reset();

        return $next($subscription);
    }
}
