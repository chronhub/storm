<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

interface ProjectionQueryScope
{
    /**
     * A projection query filter to get events from included position with limit.
     * if limit is 0, PHP_INT_MAX would be used.
     *
     * @throws InvalidArgumentException when position is less than 0
     */
    public function fromIncludedPosition(int $limit = 500): ProjectionQueryFilter;
}
