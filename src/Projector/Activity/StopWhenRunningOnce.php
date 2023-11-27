<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentProjector;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Projector\ProjectionStatus;
use Closure;

final readonly class StopWhenRunningOnce
{
    public function __construct(private PersistentProjector $projector)
    {
    }

    public function __invoke(PersistentSubscriptionInterface $subscription, Closure $next): Closure|bool
    {
        if (! $this->shouldKeepRunning($subscription) && $subscription->currentStatus() === ProjectionStatus::RUNNING) {
            $this->projector->stop();
        }

        return $next($subscription);
    }

    private function shouldKeepRunning(PersistentSubscriptionInterface $subscription): bool
    {
        return $subscription->sprint()->inBackground() && $subscription->sprint()->inProgress();
    }
}
