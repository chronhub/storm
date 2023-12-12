<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final class UpdateLockDataDTO extends ProjectionDataDTO
{
    public function __construct(string $lockedUntil)
    {
        $this->lockedUntil = $lockedUntil;
    }

    public function toArray(): array
    {
        return [
            'locked_until' => $this->lockedUntil,
        ];
    }
}
