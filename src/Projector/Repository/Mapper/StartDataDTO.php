<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final readonly class StartDataDTO extends ProjectionDataDTO
{
    public function __construct(public string $status, public string $lockedUntil)
    {
    }

    /**
     * @return array{'status': string, 'locked_until': string}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'locked_until' => $this->lockedUntil,
        ];
    }
}
