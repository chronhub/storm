<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ReadModelProjectorCaster extends ProjectorCaster
{
    /**
     * Get the read model
     */
    public function readModel(): ReadModel;
}
