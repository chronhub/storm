<?php

declare(strict_types=1);

namespace Chronhub\Storm\Stream;

use Chronhub\Storm\Contracts\Stream\StreamCategory;
use function strpos;
use function substr;

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
