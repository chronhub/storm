<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\QuerySubscriptionManagement;

final readonly class QueryProjectorScope implements QueryProjectorScopeInterface
{
    public function __construct(private QuerySubscriptionManagement $query)
    {
    }

    public function stop(): void
    {
        $this->query->stop();
    }

    public function streamName(): string
    {
        return $this->query->getCurrentStreamName();
    }

    public function clock(): SystemClock
    {
        return $this->query->getClock();
    }
}
