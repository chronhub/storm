<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Stream;

class CheckpointFactory
{
    public static function from(string $streamName, int $position, string $createdAt, array $gaps): Checkpoint
    {
        return new Checkpoint($streamName, $position, $createdAt, $gaps);
    }

    public static function fromArray(array $checkpoint): Checkpoint
    {
        return new Checkpoint(
            $checkpoint['stream_name'],
            $checkpoint['position'],
            $checkpoint['created_at'],
            $checkpoint['gaps'],
        );
    }
}
