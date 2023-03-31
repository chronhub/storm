<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Subscription\Subscription;

final class PreparePersistentRunner
{
    use RemoteStatusDiscovery;

    private bool $isInitialized = false;

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if (! $this->isInitialized) {
            $this->isInitialized = true;

            if ($this->refresh(true, $subscription->runner->inBackground())) {
                return true;
            }

            $this->repository->rise();
        }

        return $next($subscription);
    }
}
