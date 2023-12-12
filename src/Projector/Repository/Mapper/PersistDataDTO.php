<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final class PersistDataDTO extends ProjectionDataDTO
{
    public function __construct(string $state, string $position, string $lockedUntil)
    {
        $this->state = $state;
        $this->position = $position;
        $this->lockedUntil = $lockedUntil;
    }

    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'posittion' => $this->position,
            'locked_until' => $this->lockedUntil,
        ];
    }
}
