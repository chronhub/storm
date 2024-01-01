<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Checkpoint;

final readonly class UnrecoverableGapDetected
{
    public function __construct(
        public string $streamName,
        public int $position
    ) {
    }
}
