<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Stream;

final readonly class StreamProcessed
{
    public function __construct(public string $streamName)
    {
    }
}
