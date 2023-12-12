<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final readonly class CreateDataDTO extends ProjectionDataDTO
{
    public function __construct(public string $status)
    {
    }

    /**
     * @return array<"status", string>
     */
    public function toArray(): array
    {
        return [
            'stats' => $this->status,
        ];
    }
}
