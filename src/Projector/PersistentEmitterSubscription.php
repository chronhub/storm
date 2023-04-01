<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Scheme\GapDetector;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\PersistentViewSubscription;

final class PersistentEmitterSubscription implements PersistentViewSubscription
{
    use InteractWithSubscription;

    private bool $isStreamCreated = false;

    public readonly bool $isPersistent;

    public function __construct(
        protected readonly ProjectionOption $option,
        protected readonly StreamPosition $streamPosition,
        protected readonly EventCounter $eventCounter,
        protected readonly GapDetector $gap,
        protected readonly SystemClock $clock)
    {
        $this->state = new ProjectionState();
        $this->sprint = new Sprint();
        $this->isPersistent = true;
    }

    public function eventCounter(): EventCounter
    {
        return $this->eventCounter;
    }

    public function gap(): GapDetector
    {
        return $this->gap;
    }

    public function isAttached(): bool
    {
        return $this->isStreamCreated;
    }

    public function attach(): void
    {
        $this->isStreamCreated = true;
    }

    public function detach(): void
    {
        $this->isStreamCreated = false;
    }
}
