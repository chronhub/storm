<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Subscription\Subscription;

use function usleep;

final class PersistOrUpdate
{
    private int $incrementedSleeps = 1;

    public function __construct(private readonly PersistentManagement $management)
    {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        // @phpstan-ignore-next-line
        if (! $subscription->hasGapDetection() or ! $subscription->streamManager->hasGap()) {
            // The event counter is reset when no event has been loaded,
            // and, when persistWhenThresholdReached was successfully called and no more event "handled",
            // so, we sleep and try updating the lock or, we store the data
            if ($subscription->eventCounter->isReset()) {
                // todo make incremented sleep in option
                //  also make difference between a true reset and no event loaded
                // a true reset must reset the incremented sleep
                $this->doSleep($subscription->option->getSleep(), $this->incrementedSleeps);

                $this->management->update();

                $this->incrementedSleeps++;
            } else {
                $this->management->store();
            }
        }

        $this->resetIncrementedSleeps();

        return $next($subscription);
    }

    private function doSleep(int $sleep, int $num): void
    {
        while ($num !== 0) {
            usleep(microseconds: $sleep);
            $num--;
        }
    }

    private function resetIncrementedSleeps(): void
    {
        if ($this->incrementedSleeps === 10) {
            $this->incrementedSleeps = 0;
        }
    }
}
