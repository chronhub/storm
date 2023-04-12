<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use function usleep;

final readonly class PersistOrUpdateLock
{
    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        if (! $subscription->gap()->hasGap()) {
            $subscription->eventCounter()->isReset()
                ? $this->sleepBeforeUpdateLock($subscription->option()->getSleep())
                : $subscription->store();
        }

        return $next($subscription);
    }

    private function sleepBeforeUpdateLock(PersistentSubscriptionInterface $subscription, int $sleep): void
    {
        usleep(microseconds: $sleep);

        $subscription->renew();
    }
}
