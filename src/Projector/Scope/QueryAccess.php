<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScope;
use Chronhub\Storm\Projector\Subscription\Notification\Sprint\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Notification\Stream\CurrentProcessedStream;

final class QueryAccess implements ArrayAccess, QueryProjectorScope
{
    use ScopeBehaviour;

    public function __construct(
        private readonly NotificationHub $hub,
        private readonly SystemClock $clock
    ) {
    }

    public function stop(): void
    {
        $this->hub->notify(SprintStopped::class);
    }

    public function streamName(): string
    {
        return $this->hub->expect(CurrentProcessedStream::class);
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
