<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Scheme\StreamGapDetector;
use Chronhub\Storm\Projector\Scheme\StreamPosition;

final class ReadModelSubscription implements ReadModelSubscriptionInterface
{
    use InteractWithSubscription;

    public function __construct(
        protected readonly ProjectionOption $option,
        protected readonly StreamPosition $streamPosition,
        protected readonly EventCounter $eventCounter,
        protected readonly StreamGapDetector $gap,
        protected readonly SystemClock $clock
    ) {
        $this->state = new ProjectionState();
        $this->sprint = new Sprint();
        $this->isPersistent = true;
    }

    public function eventCounter(): EventCounter
    {
        return $this->eventCounter;
    }

    public function gap(): StreamGapDetector
    {
        return $this->gap;
    }
}
