<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface QueryProjector extends QueryProjectorFactory
{
    /**
     * Resets the projection.
     */
    public function reset(): void;
}
