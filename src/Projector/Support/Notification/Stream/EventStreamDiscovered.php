<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Stream;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class EventStreamDiscovered
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->discoverStreams();
    }
}