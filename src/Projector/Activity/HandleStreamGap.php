<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Closure;

final readonly class HandleStreamGap
{
    public function __invoke(PersistentSubscriptionInterface $subscription, Closure $next): Closure|bool
    {
        if ($subscription->gap()->hasGap()) {
            $subscription->gap()->sleep();

            $subscription->store();
        }

        return $next($subscription);
    }
}
