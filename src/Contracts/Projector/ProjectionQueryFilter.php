<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;

interface ProjectionQueryFilter extends QueryFilter
{
    /**
     * @param positive-int $streamPosition
     */
    public function setCurrentPosition(int $streamPosition): void;
}
