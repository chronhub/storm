<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final readonly class UpdateLockDataDTO extends ProjectionDataDTO
{
    public function __construct(public string $lockedUntil)
    {
    }

    /**
     * @return array{'locked_until': string}
     */
    public function toArray(): array
    {
        return ['locked_until' => $this->lockedUntil];
    }
}
