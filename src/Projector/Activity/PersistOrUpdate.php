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
