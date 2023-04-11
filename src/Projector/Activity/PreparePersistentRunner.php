<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionManagement;

final class PreparePersistentRunner
{
    use RemoteStatusDiscovery;

    private bool $isInitialized = false;

    public function __invoke(PersistentSubscriptionInterface $subscription, ?ProjectionManagement $repository, callable $next): callable|bool
    {
        if (! $this->isInitialized) {
            $this->repository = $repository;

            $this->isInitialized = true;

            if ($this->recoverProjectionStatus(true, $subscription->sprint()->inBackground())) {
                return true;
            }

            $this->repository->rise();
        }

        return $next($subscription, $repository);
    }
}
