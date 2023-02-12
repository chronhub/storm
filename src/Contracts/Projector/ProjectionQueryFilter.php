<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;

interface ProjectionQueryFilter extends QueryFilter
{
    /**
     * Set current stream position
     *
     * @param  int  $position
     * @return void
     */
    public function setCurrentPosition(int $position): void;
}
