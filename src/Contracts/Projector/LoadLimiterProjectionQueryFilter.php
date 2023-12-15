<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface LoadLimiterProjectionQueryFilter extends ProjectionQueryFilter
{
    public function setLoadLimiter(int $loadLimiter): void;
}
