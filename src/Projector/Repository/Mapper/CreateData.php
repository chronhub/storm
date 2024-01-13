<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final readonly class CreateData extends ProjectionDTO
{
    public function __construct(public string $status)
    {
    }

    /**
     * @return array{'status': string}
     */
    public function toArray(): array
    {
        return ['status' => $this->status];
    }
}
