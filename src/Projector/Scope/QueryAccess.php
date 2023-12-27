<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScope;
use Chronhub\Storm\Projector\Subscription\Notification\GetStreamName;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;

final class QueryAccess implements ArrayAccess, QueryProjectorScope
{
    use ScopeBehaviour;

    public function __construct(
        private readonly HookHub $task,
        private readonly SystemClock $clock
    ) {
    }

    public function stop(): void
    {
        $this->task->interact(SprintStopped::class);
    }

    public function streamName(): string
    {
        return $this->task->interact(GetStreamName::class);
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
