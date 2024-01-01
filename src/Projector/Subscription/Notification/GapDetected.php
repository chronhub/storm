<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

final readonly class GapDetected
{
    public function __construct(
        public string $streamName,
        public int $position
    ) {
    }
}
