<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Stream;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class GetNewEventStreams
{
    public function __invoke(Subscriptor $subscriptor): array
    {
        return $subscriptor->watcher()->streamDiscovery()->newEventStreams();
    }
}
