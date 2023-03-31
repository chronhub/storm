<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Scheme\StreamPosition;

interface Subscription
{
    public function compose(ContextBuilder $context, ProjectorCaster $projectorCaster, bool $keepRunning): void;

    public function initializeAgain(): void;

    public function context(): ContextRead;

    public function sprint(): Sprint;

    public function state(): PersistentState;

    public function option(): ProjectionOption;

    public function streamPosition(): StreamPosition;

    public function clock(): SystemClock;
}
