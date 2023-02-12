<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler\Exceptions;

use Throwable;

class QueryFailure extends RuntimeException
{
    public static function fromThrowable(Throwable $exception): static
    {
        return new static(
            'A query exception occurred: '.$exception->getMessage(),
            $exception->getCode(),
            $exception
        );
    }
}
