<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

use Chronhub\Storm\Contracts\Projector\ProjectionData;

abstract class ProjectionDataDTO implements ProjectionData
{
    protected ?string $status;

    protected ?string $state;

    protected ?string $position;

    protected ?string $lockedUntil;

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
