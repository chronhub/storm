<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler\Exceptions;

use Chronhub\Storm\Stream\StreamName;

class StreamAlreadyExists extends RuntimeException
{
    public static function withStreamName(StreamName $streamName): self
    {
        return new self("Stream $streamName already exists");
    }
}
