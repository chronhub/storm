<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Checkpoint;

final readonly class GapDetected
{
    public function __construct(
        public string $streamName,
        public int $position
    ) {
    }
}
