<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ReadModelProjector extends PersistentProjector
{
    /**
     * Return the current read model
     *
     * @return ReadModel
     */
    public function readModel(): ReadModel;
}
