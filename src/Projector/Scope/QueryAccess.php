<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScope;
use Chronhub\Storm\Projector\Subscription\Notification\ExpectProcessedStream;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;

final class QueryAccess implements ArrayAccess, QueryProjectorScope
{
    use ScopeBehaviour;

    public function __construct(
        private readonly HookHub $hub,
        private readonly SystemClock $clock
    ) {
    }

    public function stop(): void
    {
        $this->hub->notify(SprintStopped::class);
    }

    public function streamName(): string
    {
        return $this->hub->expect(ExpectProcessedStream::class);
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
