<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final readonly class UpdateStatusDataDto extends ProjectionDataDTO
{
    public function __construct(public string $status)
    {
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
        ];
    }
}
