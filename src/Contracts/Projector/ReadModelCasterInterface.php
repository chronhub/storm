<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ReadModelCasterInterface extends Caster
{
    public function readModel(): ReadModel;
}
