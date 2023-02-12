<?php

namespace Chronhub\Storm\Stream;

use Chronhub\Storm\Contracts\Stream\StreamCategory;

final readonly class DetermineStreamCategory implements StreamCategory
{
    public function __construct(public string $separator = '-')
    {
    }

    public function __invoke(string $streamName): ?string
    {
        $pos = strpos($streamName, $this->separator);

        return $pos !== false && $pos > 0 ? substr($streamName, 0, $pos) : null;
    }
}