<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ApiProjectionQueryFilter extends ProjectionQueryFilter
{
    public function setCurrentStreamName(string $streamName): void;
}
