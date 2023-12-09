<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Subscription\Beacon;

final readonly class StopWhenRunningOnce
{
    public function __construct(private PersistentSubscriber $subscription)
    {
    }

    public function __invoke(Beacon $manager, callable $next): callable|bool
    {
        if (! $this->shouldKeepRunning($manager) && $manager->currentStatus() === ProjectionStatus::RUNNING) {
            $this->subscription->close();
        }

        return $next($manager);
    }

    private function shouldKeepRunning(Beacon $manager): bool
    {
        return $manager->sprint->inBackground() && $manager->sprint->inProgress();
    }
}
