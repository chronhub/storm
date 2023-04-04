<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Scheme\StreamGapDetector;
use Chronhub\Storm\Projector\Scheme\StreamPosition;

final class EmitterSubscription implements EmitterSubscriptionInterface
{
    use InteractWithSubscription;

    private bool $streamFixed = false;

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

    public function isJoined(): bool
    {
        return $this->streamFixed;
    }

    public function join(): void
    {
        $this->streamFixed = true;
    }

    public function disjoin(): void
    {
        $this->streamFixed = false;
    }
}
