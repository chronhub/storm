<?php

declare(strict_types=1);

namespace Chronhub\Storm\Stream;

use Stringable;
use InvalidArgumentException;

final readonly class StreamName implements Stringable
{
    public function __construct(public string $name)
    {
        if ($this->name === '') {
            throw new InvalidArgumentException('Stream name given can not be empty');
        }
    }

    public function toString(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
