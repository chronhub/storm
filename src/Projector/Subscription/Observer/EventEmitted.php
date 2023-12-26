<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Observer;

use Chronhub\Storm\Reporter\DomainEvent;

final readonly class EventEmitted
{
    public function __construct(public DomainEvent $event)
    {
    }
}
