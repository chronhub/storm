<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Provider\Checkpoint;

use Chronhub\Storm\Contracts\Projector\CheckpointModel;

use function sha1;

final readonly class InMemoryCheckpointModel implements CheckpointModel
{
    public function __construct(
        public string $projectionName,
        public string $streamName,
        public int $position,
        public string $eventTime,
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

    public function eventTime(): string
    {
        return $this->eventTime;
    }

    public function createdAt(): string
    {
        return $this->createdAt;
    }

    public function gaps(): string
    {
        return $this->gaps;
    }
}
