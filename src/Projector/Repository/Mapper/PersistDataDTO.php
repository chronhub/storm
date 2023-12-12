<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final readonly class PersistDataDTO extends ProjectionDataDTO
{
    public function __construct(
        public string $state,
        public string $position,
        public string $lockedUntil
    ) {
    }

    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'position' => $this->position,
            'locked_until' => $this->lockedUntil,
        ];
    }
}
