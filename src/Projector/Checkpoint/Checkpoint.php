<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Checkpoint;

use JsonSerializable;

use function in_array;

final readonly class Checkpoint implements JsonSerializable
{
    public function __construct(
        public string $streamName,
        public int $position,
        public ?string $eventTime,
        public string $createdAt,
        public array $gaps,
        public ?GapType $type = null
    ) {
    }

    public function isGap(): bool
    {
        if ($this->type !== null) {
            return true;
        }

        return in_array($this->position - 1, $this->gaps, true);
    }

    /**
     * @return array{stream_name: string, position: int<0,max>, event_time: ?string, created_at: string, gaps: array<positive-int>}
     */
    public function jsonSerialize(): array
    {
        return [
            'stream_name' => $this->streamName,
            'position' => $this->position,
            'event_time' => $this->eventTime,
            'created_at' => $this->createdAt,
            'gaps' => $this->gaps,
        ];
    }
}
