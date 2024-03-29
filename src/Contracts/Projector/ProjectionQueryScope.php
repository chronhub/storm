<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectionQueryScope
{
    /**
     * A projection query filter to get events from included position.
     */
    public function fromIncludedPosition(): ProjectionQueryFilter;
}
