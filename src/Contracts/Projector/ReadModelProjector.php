<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ReadModelProjector extends PersistentProjector
{
    /**
     * Return read model instance.
     */
    public function readModel(): ReadModel;
}
