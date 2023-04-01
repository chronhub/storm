<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;

final class QuerySubscription implements Subscription
{
    use InteractWithSubscription;

    public readonly bool $isPersistent;

    public function __construct(
        protected readonly ProjectionOption $option,
        protected readonly StreamPosition $streamPosition,
        protected readonly SystemClock $clock)
    {
        $this->state = new ProjectionState();
        $this->sprint = new Sprint();
        $this->isPersistent = false;
    }
}
