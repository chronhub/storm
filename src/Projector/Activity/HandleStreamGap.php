<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final readonly class HandleStreamGap
{
    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        if ($subscription->gap()->hasGap()) {
            $subscription->gap()->sleep();

            $subscription->store();
        }

        return $next($subscription);
    }
}
