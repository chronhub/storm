<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Hook;

use Chronhub\Storm\Reporter\DomainEvent;

final readonly class EventLinkedTo
{
    public function __construct(
        public string $streamName,
        public DomainEvent $event
    ) {
    }
}
