<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface LoadLimiterProjectionQueryFilter extends ProjectionQueryFilter
{
    /**
     * Set the number of stream events to load
     */
    public function setLoadLimiter(int $loadLimiter): void;
}
