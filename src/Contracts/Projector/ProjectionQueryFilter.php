<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;

interface ProjectionQueryFilter extends QueryFilter
{
    /**
     * Set current stream position
     */
    public function setCurrentPosition(int $position): void;
}
