<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentProjector;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Projector\ProjectionStatus;
use Closure;

use function in_array;

final readonly class StopWhenRunningOnce
{
    public function __construct(private PersistentProjector $projector)
    {
    }

    public function __invoke(PersistentSubscriptionInterface $subscription, Closure $next): Closure|bool
    {
        if (! $this->shouldKeepRunning($subscription) && $this->hasStatus($subscription)) {
            $this->projector->stop();
        }

        return $next($subscription);
    }

    private function shouldKeepRunning(PersistentSubscriptionInterface $subscription): bool
    {
        return $subscription->sprint()->inBackground() && $subscription->sprint()->inProgress();
    }

    private function hasStatus(PersistentSubscriptionInterface $subscription): bool
    {
        return in_array($subscription->currentStatus(), [
            ProjectionStatus::RUNNING,
            ProjectionStatus::IDLE,
        ]);
    }
}
