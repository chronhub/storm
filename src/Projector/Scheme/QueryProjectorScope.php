<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\QuerySubscriber;

final readonly class QueryProjectorScope implements QueryProjectorScopeInterface
{
    public function __construct(private QuerySubscriber $subscriber)
    {
    }

    public function stop(): void
    {
        $this->subscriber->subscription->sprint->stop();
    }

    public function streamName(): string
    {
        return $this->subscriber->subscription->currentStreamName();
    }

    public function clock(): SystemClock
    {
        return $this->subscriber->subscription->clock;
    }
}
