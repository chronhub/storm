<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler\Exceptions;

use Chronhub\Storm\Stream\StreamName;
use function sprintf;

class StreamNotFound extends RuntimeException
{
    public static function withStreamName(StreamName $streamName): self
    {
        return new static(sprintf('Stream %s not found', $streamName->name));
    }
}
