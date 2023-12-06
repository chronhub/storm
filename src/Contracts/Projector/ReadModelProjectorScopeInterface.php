<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ReadModelProjectorScopeInterface extends ProjectorScope
{
    /**
     * Return the read model instance.
     */
    public function readModel(): ReadModel;
}
