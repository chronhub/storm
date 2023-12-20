<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Stream;

use JsonSerializable;

final readonly class Checkpoint implements JsonSerializable
{
    public function __construct(
        public string $streamName,
        public int $position,
        public string $createdAt,
        public array $gaps
    ) {
    }

    /**
     * @return array{stream_name: string, position: int<0,max>, created_at: string, gaps: array<positive-int>}
     */
    public function jsonSerialize(): array
    {
        return [
            'stream_name' => $this->streamName,
            'position' => $this->position,
            'created_at' => $this->createdAt,
            'gaps' => $this->gaps,
        ];
    }
}
