<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Options;

use Chronhub\Storm\Contracts\Projector\ProjectionOption;

final class DefaultProjectionOption implements ProjectionOption
{
    use ProvideProjectionOption;

    public function __construct(
        protected readonly bool $signal = false,
        protected readonly int $cacheSize = 1000,
        protected readonly int $blockSize = 1000,
        protected readonly int $sleep = 100000,
        protected readonly int $timeout = 1000,
        protected readonly int $lockout = 100000,
        array|string $retries = [0, 5, 50, 100, 150, 200, 250, 500, 750, 1000],
        protected readonly ?string $detectionWindows = null)
    {
        $this->setUpRetries($retries);
    }
}
