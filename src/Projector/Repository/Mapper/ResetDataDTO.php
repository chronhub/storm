<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final readonly class ResetDataDTO extends ProjectionDataDTO
{
    public function __construct(
        public string $status,
        public string $state,
        public string $position
    ) {
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
