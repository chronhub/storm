<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Reporter\DomainEvent;

final readonly class GapDetected
{
    public function __construct(
        public string $streamName,
        public DomainEvent $event,
        public int $position
    ) {
    }
}
