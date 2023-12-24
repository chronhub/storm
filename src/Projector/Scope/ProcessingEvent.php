<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use Chronhub\Storm\Reporter\DomainEvent;

class ProcessingEvent
{
    public function __construct(
        private DomainEvent $event,
        private ?array $userState
    )
    {
    }
}
