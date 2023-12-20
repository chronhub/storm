<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Stream;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;

class EventStreamDiscovery
{
    public function __construct(protected readonly EventStreamProvider $eventStreamProvider)
    {
    }

    public function query(callable $query): array
    {
        return $query($this->eventStreamProvider);
    }
}
