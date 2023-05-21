<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final class PreparePersistentRunner
{
    use RemoteStatusDiscovery;

    private bool $isFirstExecution = true;

    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        if (! $this->isFirstExecution) {
            $this->isFirstExecution = false;

            if ($this->shouldStopOnDiscloseStatus($subscription)) {
                return true;
            }

            $subscription->rise();
        }

        return $next($subscription);
    }

    public function isFirstExecution(): bool
    {
        return $this->isFirstExecution;
    }
}
