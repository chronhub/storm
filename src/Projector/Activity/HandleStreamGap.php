<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Closure;

final class HandleStreamGap
{
    public function __invoke(PersistentSubscriptionInterface $subscription, Closure $next): Closure|bool
    {
        if ($subscription->streamManager()->hasGap()) {
            $subscription->streamManager()->sleep();

            $subscription->store();
        }

        return $next($subscription);
    }
}
