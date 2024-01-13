<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final readonly class ResetData extends ProjectionDTO
{
    public function __construct(
        public string $status,
        public string $state,
        public string $checkpoint
    ) {
    }

    /**
     * @return array{'status': string, 'state': string, 'checkpoint': string}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'state' => $this->state,
            'checkpoint' => $this->checkpoint,
        ];
    }
}
