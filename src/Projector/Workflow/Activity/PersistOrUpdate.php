<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Projector\Support\NoStreamLoadedCounter;

final readonly class PersistOrUpdate
{
    public function __construct(
        private PersistentManagement $management,
        private NoStreamLoadedCounter $noEventCounter,
    ) {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if ($this->hasGap($subscription)) {
            return $next($subscription);
        }

        if (! $this->handleResetEventCounter($subscription)) {
            $this->noEventCounter->reset();
            $this->management->store();
        }

        return $next($subscription);
    }

    /**
     * The event counter is reset when no event has been loaded,
     * and, when persistWhenThresholdReached was successfully called and no more event "handled",
     * so, we sleep and try updating the lock.
     */
    private function handleResetEventCounter(Subscription $subscription): bool
    {
        if ($subscription->eventCounter->isReset()) {
            $this->noEventCounter->sleep();
            $this->management->update();

            return true;
        }

        return false;
    }

    private function hasGap(Subscription $subscription): bool
    {
        return $subscription->hasGapDetection() && $subscription->streamManager->hasGap();
    }
}
