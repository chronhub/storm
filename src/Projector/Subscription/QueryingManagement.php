<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\QueryManagement;

final readonly class QueryingManagement implements QueryManagement
{
    public function __construct(private Subscription $subscription)
    {
    }

    public function close(): void
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
