<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Management;
use Chronhub\Storm\Projector\Subscription\Subscription;

use function usleep;

final readonly class PersistOrUpdate
{
    public function __construct(private Management $management)
    {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if (! $subscription->hasGapDetection() or ! $subscription->streamManager->hasGap()) {
            // The event counter is reset when no event has been loaded,
            // and, when persistWhenThresholdReached was successfully called and no more event "handled",
            // so, we sleep and try updating the lock or, we store the data
            if ($subscription->eventCounter->isReset()) {
                usleep(microseconds: $subscription->option->getSleep());

                $this->management->update();
            } else {
                $this->management->store();
            }
        }

        return $next($subscription);
    }
}
