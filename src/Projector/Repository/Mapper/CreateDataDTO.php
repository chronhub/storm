<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final class CreateDataDTO extends ProjectionDataDTO
{
    public function __construct(string $status)
    {
        $this->status = $status;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
        ];
    }
}
