<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScope;
use Chronhub\Storm\Projector\Subscription\Notification;

final class QueryAccess implements ArrayAccess, QueryProjectorScope
{
    use ScopeBehaviour;

    public function __construct(
        private readonly Notification $notification,
        private readonly SystemClock $clock
    ) {
    }

    public function stop(): void
    {
        $this->notification->onProjectionStopped();
    }

    public function streamName(): string
    {
        return $this->notification->observeStreamName();
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
