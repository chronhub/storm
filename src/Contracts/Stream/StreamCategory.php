<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Stream;

interface StreamCategory
{
    /**
     * @return string|null
     */
    public function __invoke(string $streamName): ?string;
}
