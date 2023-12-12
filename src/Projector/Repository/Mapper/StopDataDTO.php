<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final readonly class StopDataDTO extends ProjectionDataDTO
{
    public function __construct(
        public string $status,
        public string $state,
        public string $position,
        public string $lockedUntil
    ) {
    }

    /**
     * @return array{status: string, state: string, position: string, locked_until: string}
     */
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
