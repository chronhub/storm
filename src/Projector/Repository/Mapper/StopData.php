<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final readonly class StopData extends ProjectionDTO
{
    public function __construct(
        public string $status,
        public string $state,
        public string $checkpoint,
        public string $lockedUntil
    ) {
    }

    /**
     * @return array{'status': string, 'state': string, 'checkpoint': string, 'locked_until': string}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'state' => $this->state,
            'checkpoint' => $this->checkpoint,
            'locked_until' => $this->lockedUntil,
        ];
    }
}
