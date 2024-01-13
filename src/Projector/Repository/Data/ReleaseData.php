<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Data;

final readonly class ReleaseData extends ProjectionDTO
{
    public function __construct(public string $status, public null $lockedUntil)
    {
    }

    /**
     * @return array{'status': string, 'locked_until': null}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'locked_until' => $this->lockedUntil,
        ];
    }
}
