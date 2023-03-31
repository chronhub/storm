<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Projector\Scheme\DetectGap;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Scheme\KeepRunning;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;

final class PersistentSubscription implements Subscription
{
    use ProvideSubscription;

    // todo seperated from read model with subscription interface
    public bool $isStreamCreated = false;

    public readonly bool $isPersistent;

    public function __construct(
        public readonly ProjectionOption $option,
        public readonly StreamPosition $streamPosition,
        public readonly EventCounter $eventCounter,
        public readonly DetectGap $gap,
        public readonly SystemClock $clock)
    {
        $this->state = new ProjectionState();
        $this->runner = new KeepRunning();
        $this->isPersistent = true;
    }
}
