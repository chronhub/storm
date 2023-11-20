<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ReadModelProjectorScopeInterface extends ProjectorScope
{
    /**
     * Return read model instance
     */
    public function readModel(): ReadModel;
}
