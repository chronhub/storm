<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Data;

use Chronhub\Storm\Contracts\Projector\ProjectionData;

abstract readonly class ProjectionDTO implements ProjectionData
{
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
