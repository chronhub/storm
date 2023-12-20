<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Provider\Checkpoint;

use function sha1;

final readonly class InMemoryCheckpointModel
{
    public function __construct(
        public string $projectionName,
        public string $streamName,
        public int $position,
        public string $createdAt,
        public ?string $gaps = '{}'
    ) {
    }

    public function id(): string
    {
        return sha1($this->projectionName.':'.$this->streamName.':'.$this->position);
    }

    public function projectionName(): string
    {
        return $this->projectionName;
    }

    public function streamName(): string
    {
        return $this->streamName;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function createdAt(): string
    {
        return $this->createdAt;
    }

    public function gaps(): string
    {
        return $this->gaps;
    }

    public function isEqualTo(InMemoryCheckpointModel $checkpoint): bool
    {
        return $this->id() === $checkpoint->id();
    }
}
