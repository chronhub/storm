<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Provider\Checkpoint;

final readonly class CheckpointDTO
{
    public function __construct(
        public string $projectionName,
        public string $streamName,
        public int $position,
        public string $eventTime,
        public string $createdAt,
        public string $gaps
    ) {
    }
}
