<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

final class RecoverableGapDetected
{
    public function __construct(
        public string $streamName,
        public int $position
    ) {
    }
}
