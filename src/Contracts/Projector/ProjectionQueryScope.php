<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

interface ProjectionQueryScope
{
    /**
     * @throws InvalidArgumentException when position is less or equals than 0
     */
    public function fromIncludedPosition(): ProjectionQueryFilter;

    /**
     * @throws InvalidArgumentException when position is less or equals than 0
     */
    public function fromIncludedPositionWithLimit(int $limit = 1000): ProjectionQueryFilter;
}
