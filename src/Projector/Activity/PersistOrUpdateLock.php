<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use function usleep;

final readonly class PersistOrUpdateLock
{
    public function __invoke(PersistentSubscriptionInterface $subscription, ?ProjectionManagement $repository, callable $next): callable|bool
    {
        if (! $subscription->gap()->hasGap()) {
            $subscription->eventCounter()->isReset()
                ? $this->sleepBeforeUpdateLock($repository, $subscription->option()->getSleep())
                : $repository->store();
        }

        return $next($subscription, $repository);
    }

    private function sleepBeforeUpdateLock(ProjectionManagement $repository, int $sleep): void
    {
        usleep(microseconds: $sleep);

        $repository->renew();
    }
}
