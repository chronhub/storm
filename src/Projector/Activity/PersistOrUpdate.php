<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Scheme\NoStreamLoadedCounter;
use Chronhub\Storm\Projector\Subscription\Subscription;

final readonly class PersistOrUpdate
{
    public function __construct(
        private PersistentManagement $management,
        private NoStreamLoadedCounter $noEventCounter,
    ) {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        // @phpstan-ignore-next-line
        if (! $subscription->hasGapDetection() or ! $subscription->streamManager->hasGap()) {
            // The event counter is reset when no event has been loaded,
            // and, when persistWhenThresholdReached was successfully called and no more event "handled",
            // so, we sleep and try updating the lock or, we store the data
            if ($subscription->eventCounter->isReset()) {
                $this->noEventCounter->sleep();
                $this->management->update();
            } else {
                dump('store');
                $this->noEventCounter->reset();
                $this->management->store();
            }
        }

        return $next($subscription);
    }
}
