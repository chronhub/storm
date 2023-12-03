<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface LoadLimiterProjectionQueryFilter extends ProjectionQueryFilter
{
    //todo add setLimit to projection query filter?
    public function setLimit(int $limit): void;
}
