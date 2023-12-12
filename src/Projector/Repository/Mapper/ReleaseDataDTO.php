<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final class ReleaseDataDTO extends ProjectionDataDTO
{
    public function __construct(string $status, null $lockedUntil)
    {
        $this->status = $status;
        $this->lockedUntil = $lockedUntil;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'locked_until' => $this->lockedUntil,
        ];
    }
}
