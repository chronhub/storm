<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Stream;

class CheckpointFactory
{
    public static function from(
        string $streamName,
        int $position,
        ?string $eventTime,
        string $createdAt,
        array $gaps,
        ?GapType $gapType
    ): Checkpoint {
        return new Checkpoint($streamName, $position, $eventTime, $createdAt, $gaps, $gapType);
    }

    public static function fromArray(array $checkpoint): Checkpoint
    {
        return new Checkpoint(
            $checkpoint['stream_name'],
            $checkpoint['position'],
            $checkpoint['created_at'],
            $checkpoint['event_time'],
            $checkpoint['gaps'],
            null
        );
    }
}
