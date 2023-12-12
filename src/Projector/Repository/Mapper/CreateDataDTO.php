<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Mapper;

final readonly class CreateDataDTO extends ProjectionDataDTO
{
    public function __construct(public string $status)
    {
    }

    /**
     * @return array<object, string>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
        ];
    }
}
