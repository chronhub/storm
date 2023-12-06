<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

use function usleep;

final readonly class PersistOrUpdate
{
    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        if (! $subscription->streamManager()->hasGap()) {

            // The event counter is reset when no event has been handled,
            // and, when persistWhenThresholdReached was successfully called,
            // so, we sleep and try updating the lock or, we store the data
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
