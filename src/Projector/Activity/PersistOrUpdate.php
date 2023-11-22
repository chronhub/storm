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
             * When the projection block size is reached,
             * we update the projection if the lock can be refreshed,
             * and add a brief pause before proceeding with the update.
             * Otherwise, we persist the whole projection.
             */
            $subscription->eventCounter()->isReset() ? $this->sleepBeforeUpdate($subscription) : $subscription->store();
        }

        return $next($subscription);
    }

    private function sleepBeforeUpdate(PersistentSubscriptionInterface $subscription): void
    {
        usleep(microseconds: $subscription->option()->getSleep());

        $subscription->update();
    }
}
