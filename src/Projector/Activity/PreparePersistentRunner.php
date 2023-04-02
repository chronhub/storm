<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;

final class PreparePersistentRunner
{
    use RemoteStatusDiscovery;

    private bool $isInitialized = false;

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if (! $this->isInitialized) {
            $this->isInitialized = true;

            if ($this->recoverProjectionStatus(true, $subscription->sprint()->inBackground())) {
                return true;
            }

            $this->repository->rise();
        }

        return $next($subscription);
    }
}
