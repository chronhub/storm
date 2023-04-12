<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final class PreparePersistentRunner
{
    use RemoteStatusDiscovery;

    private bool $isInitialized = false;

    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        if (! $this->isInitialized) {
            $this->subscription = $subscription;

            $this->isInitialized = true;

            if ($this->recoverProjectionStatus(true, $subscription->sprint()->inBackground())) {
                return true;
            }

            $this->subscription->rise();
        }

        return $next($subscription);
    }

    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }
}
