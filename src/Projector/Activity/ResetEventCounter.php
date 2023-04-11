<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionManagement;

final class ResetEventCounter
{
    public function __invoke(PersistentSubscriptionInterface $subscription, ?ProjectionManagement $repository, callable $next): callable|bool
    {
        $subscription->eventCounter()->reset();

        return $next($subscription, $repository);
    }
}
