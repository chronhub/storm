<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Closure;

use function usleep;

final readonly class PersistOrUpdate
{
    public function __invoke(PersistentSubscriptionInterface $subscription, Closure $next): Closure|bool
    {
        if (! $subscription->streamManager()->hasGap()) {
            /**
             * Counter is reset when no event has been handled and
             * in rare case when the loaded/handled events match exactly the option block size
             * so, we sleep to avoid too much query and update the lock or persist the projection
             */
            if ($subscription->eventCounter()->isReset()) {
                usleep(microseconds: $subscription->option()->getSleep());

                $subscription->update();
            } else {
                $subscription->store();
            }
        }

        return $next($subscription);
    }
}
