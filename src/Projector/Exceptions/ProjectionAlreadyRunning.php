<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Exceptions;

class ProjectionAlreadyRunning extends RuntimeException
{
    public static function withName(string $name): self
    {
        $message = "Acquiring lock failed for stream name: $name: ";
        $message .= 'another projection process is already running or ';
        $message .= 'wait till the released process complete';

        return new self($message);
    }
}
