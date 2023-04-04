<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use function usleep;

final readonly class PersistOrUpdateLock
{
    public function __construct(private ProjectionManagement $repository)
    {
    }

    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        if (! $subscription->gap()->hasGap()) {
            $subscription->eventCounter()->isReset()
                ? $this->sleepBeforeUpdateLock($subscription->option()->getSleep())
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
