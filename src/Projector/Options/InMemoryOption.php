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
        protected readonly int $blockSize = 1,
        protected readonly int $sleep = 100,
        protected readonly int $timeout = 1,
        protected readonly int $lockout = 0,
        array|string $retries = [],
        protected readonly ?int $loads = 100,
        protected readonly ?string $detectionWindows = null,
    ) {
        $this->setUpRetries($retries);
    }
}
