<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use Chronhub\Storm\Projector\ProjectionStatus;

final readonly class StopWhenRunningOnce
{
    public function __construct(private SubscriptionManagement $subscription)
    {
    }

    public function __invoke(PersistentSubscriber $subscriber, callable $next): callable|bool
    {
        if (! $this->shouldKeepRunning($subscriber) && $subscriber->currentStatus() === ProjectionStatus::RUNNING) {
            $this->subscription->close();
        }

        return $next($subscriber);
    }

    private function shouldKeepRunning(PersistentSubscriber $subscriber): bool
    {
        return $subscriber->sprint->inBackground() && $subscriber->sprint->inProgress();
    }
}
