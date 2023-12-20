<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Provider\Checkpoint;

use Stringable;

use function sha1;

final readonly class CheckpointId implements Stringable
{
    public function __construct(
        public string $projectionName,
        public string $streamName,
        public int $position,
    ) {
    }

    public function toString(): string
    {
        return sha1($this->projectionName.':'.$this->streamName.':'.$this->position);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
