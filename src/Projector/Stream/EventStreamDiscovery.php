<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Stream;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;

readonly class EventStreamDiscovery
{
    public function __construct(protected EventStreamProvider $eventStreamProvider)
    {
    }

    public function discover(callable $query): array
    {
        return $query($this->eventStreamProvider);
    }
}
