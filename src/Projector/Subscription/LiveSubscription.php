<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Scheme\KeepRunning;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;

final class LiveSubscription implements Subscription
{
    use ProvideSubscription;

    public readonly bool $isPersistent;

    public function __construct(
        public readonly ProjectionOption $option,
        public readonly StreamPosition $streamPosition,
        public readonly SystemClock $clock)
    {
        $this->state = new ProjectionState();
        $this->runner = new KeepRunning();
        $this->isPersistent = false;
    }
}
