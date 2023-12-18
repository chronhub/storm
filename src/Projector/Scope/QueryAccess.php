<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\QueryManagement;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScope;

final readonly class QueryAccess implements QueryProjectorScope
{
    public function __construct(private QueryManagement $query)
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
