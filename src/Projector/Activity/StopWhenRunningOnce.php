<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Projector\ProjectionStatus;
use Closure;

final readonly class StopWhenRunningOnce
{
    public function __invoke(PersistentSubscriptionInterface $subscription, Closure $next): Closure|bool
    {
        if (! $this->shouldKeepRunning($subscription) && $subscription->currentStatus() === ProjectionStatus::RUNNING) {
            $subscription->close();
        }

        return $next($subscription);
    }

    private function shouldKeepRunning(PersistentSubscriptionInterface $subscription): bool
    {
        return $subscription->sprint()->inBackground() && $subscription->sprint()->inProgress();
    }
}
