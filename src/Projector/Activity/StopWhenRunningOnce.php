<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Subscription\Subscription;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;

// do we need this class ?
// it should release the lock anyway

/**
 * @deprecated too much interference
 */
final readonly class StopWhenRunningOnce
{
    public function __construct(private SubscriptionManagement $subscription)
    {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if (! $this->shouldKeepRunning($subscription)) {
            $this->subscription->close();
        }

        return $next($subscription);
    }

    private function shouldKeepRunning(Subscription $subscription): bool
    {
        return $subscription->sprint->inBackground() && $subscription->sprint->inProgress()
            && $subscription->currentStatus() === ProjectionStatus::RUNNING;
    }
}
