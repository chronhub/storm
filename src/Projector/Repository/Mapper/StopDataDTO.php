<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final class StopDataDTO extends ProjectionDataDTO
{
    public function __construct(string $status, string $state, string $position, string $lockedUntil)
    {
        $this->status = $status;
        $this->state = $state;
        $this->position = $position;
        $this->lockedUntil = $lockedUntil;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'state' => $this->state,
            'position' => $this->position,
            'locked_until' => $this->lockedUntil,
        ];
    }
}
