<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final readonly class StartDataDTO extends ProjectionDataDTO
{
    public function __construct(public string $status, public string $lockedUntil)
    {
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'locked_until' => $this->lockedUntil,
        ];
    }
}
