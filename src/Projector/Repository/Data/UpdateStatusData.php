<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Data;

final readonly class UpdateStatusData extends ProjectionDTO
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
