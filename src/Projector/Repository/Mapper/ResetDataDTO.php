<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final class ResetDataDTO extends ProjectionDataDTO
{
    public function __construct(string $status, string $state, string $position)
    {
        $this->status = $status;
        $this->state = $state;
        $this->position = $position;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'state' => $this->state,
            'position' => $this->position,
        ];
    }
}
