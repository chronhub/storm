<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ReadModelProjector extends PersistentProjector
{
    public function readModel(): ReadModel;
}
