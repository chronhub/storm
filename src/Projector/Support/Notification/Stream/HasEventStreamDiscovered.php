<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Stream;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class HasEventStreamDiscovered
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->streamDiscovery()->hasEventStreams();
    }
}
