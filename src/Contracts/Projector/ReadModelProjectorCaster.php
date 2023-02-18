<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ReadModelProjectorCaster extends ProjectorCaster
{
    public function readModel(): ReadModel;
}
