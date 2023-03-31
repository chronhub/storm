<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use function usleep;

final readonly class PersistOrUpdateLock
{
    public function __construct(private SubscriptionManagement $repository)
    {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if (! $subscription->gap->hasGap()) {
            $subscription->eventCounter->isReset()
                ? $this->sleepBeforeUpdateLock($subscription->option->getSleep())
                : $this->repository->store();
        }

        return $next($subscription);
    }

    private function sleepBeforeUpdateLock(int $sleep): void
    {
        usleep(microseconds: $sleep);

        $this->repository->renew();
    }
}
