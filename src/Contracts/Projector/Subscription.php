<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Scheme\StreamPosition;

/**
 * @property ?string $currentStreamName
 */
interface Subscription
{
    public function compose(ContextInterface $context, Caster $projectorCaster, bool $keepRunning): void;

    public function initializeAgain(): void;

    public function currentStatus(): ProjectionStatus;

    public function setStatus(ProjectionStatus $status): void;

    public function context(): ContextReader;

    public function sprint(): Sprint;

    public function state(): ProjectionStateInterface;

    public function option(): ProjectionOption;

    public function streamPosition(): StreamPosition;

    public function clock(): SystemClock;
}
