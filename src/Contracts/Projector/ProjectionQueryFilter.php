<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;

interface ProjectionQueryFilter extends QueryFilter
{
    /**
     * @param int<1,max> $streamPosition
     */
    public function setCurrentPosition(int $streamPosition): void;
}
