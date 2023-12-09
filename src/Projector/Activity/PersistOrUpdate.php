<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Projector\Subscription\Beacon;

use function usleep;

final readonly class PersistOrUpdate
{
    public function __construct(private PersistentSubscriber $subscription)
    {
    }

    public function __invoke(Beacon $manager, callable $next): callable|bool
    {
        if (! $manager->streamBinder->hasGap()) {
            // The event counter is reset when no event has been loaded,
            // and, when persistWhenThresholdReached was successfully called,
            // so, we sleep and try updating the lock or, we store the data
            if ($this->subscription->eventCounter()->isReset()) {
                usleep(microseconds: $manager->option->getSleep());

                $this->subscription->update();
            } else {
                $this->subscription->store();
            }
        }

        return $next($manager);
    }
}
