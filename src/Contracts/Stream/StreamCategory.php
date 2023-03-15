<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Stream;

interface StreamCategory
{
    /**
     * Return string category if it matched separator, otherwise null.
     */
    public function determineFrom(string $streamName): ?string;
}
