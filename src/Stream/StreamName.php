<?php

declare(strict_types=1);

namespace Chronhub\Storm\Stream;

use InvalidArgumentException;
use Stringable;

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
