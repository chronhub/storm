<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final class HandleStreamGap
{
    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        /**
         * When a gap is detected and still retry left,
         * we sleep and store the projection if some event(s) has been handled
         */
        if ($subscription->streamManager()->hasGap()) {
            $subscription->streamManager()->sleep();

            if (! $subscription->eventCounter()->isReset()) {
                $subscription->store();
            }
        }

        return $next($subscription);
    }
}
