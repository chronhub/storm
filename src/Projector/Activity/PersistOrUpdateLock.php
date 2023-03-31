<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\PersistentSubscription;
use function usleep;

final readonly class PersistOrUpdateLock
{
    public function __construct(private ProjectionRepository $repository)
    {
    }

    public function __invoke(PersistentSubscription $subscription, callable $next): callable|bool
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
