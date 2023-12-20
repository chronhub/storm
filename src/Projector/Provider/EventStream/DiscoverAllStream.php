<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Provider\EventStream;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;

final class DiscoverAllStream
{
    public function __invoke(EventStreamProvider $provider): array
    {
        return $provider->allWithoutInternal();
    }
}
