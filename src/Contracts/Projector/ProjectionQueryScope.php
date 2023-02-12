<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

interface ProjectionQueryScope
{
    /**
     * Filter projection query from included position
     *
     * @return ProjectionQueryFilter
     *
     * @throws InvalidArgumentException when position is less or equals than 0
     */
    public function fromIncludedPosition(): ProjectionQueryFilter;
}
