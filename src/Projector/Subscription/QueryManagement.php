<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\QuerySubscriptionManagement;

final readonly class QueryManagement implements QuerySubscriptionManagement
{
    public function __construct(private Subscription $subscription)
    {
    }

    public function stop(): void
    {
        $this->subscription->sprint->stop();
    }

    public function getCurrentStreamName(): string
    {
        return $this->subscription->currentStreamName();
    }

    public function getClock(): SystemClock
    {
        return $this->subscription->clock;
    }
}
