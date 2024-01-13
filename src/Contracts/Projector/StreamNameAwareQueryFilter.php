<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface StreamNameAwareQueryFilter extends ProjectionQueryFilter
{
    /**
     * Set the stream name to filter.
     */
    public function setStreamName(string $streamName): void;
}
