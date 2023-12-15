<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface StreamNameAwareQueryFilter extends ProjectionQueryFilter
{
    public function setStreamName(string $streamName): void;
}
