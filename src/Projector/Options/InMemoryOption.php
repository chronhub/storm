<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Options;

use Chronhub\Storm\Contracts\Projector\ProjectionOption;

final class InMemoryOption implements ProjectionOption
{
    use ProvideOption;

    public function __construct(
        protected readonly bool $signal = false,
        protected readonly int $cacheSize = 100,
        protected readonly int $blockSize = 100,
        protected readonly int|array $sleep = [5, 2.5], // [5, 2.5] 5 queries every 2 seconds
        protected readonly int $timeout = 1,
        protected readonly int $lockout = 0,
        protected readonly int $loadLimiter = 100,
        array|string $retries = [],
        protected readonly ?string $detectionWindows = null,
        protected readonly bool $onlyOnceDiscovery = false,
    ) {
        $this->setUpRetries($retries);
    }
}
