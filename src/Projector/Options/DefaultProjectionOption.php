<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Options;

use Chronhub\Storm\Contracts\Projector\ProjectionOption;

final class DefaultProjectionOption implements ProjectionOption
{
    use ProvideProjectionOption;

    public function __construct(
        public readonly bool $signal = false,
        public readonly int $cacheSize = 1000,
        public readonly int $blockSize = 1000,
        public readonly int $sleep = 100000,
        public readonly int $timeout = 1000,
        public readonly int $lockout = 100000,
        array|string $retries = [0, 5, 50, 100, 150, 200, 250, 500, 750, 1000],
        public readonly ?string $detectionWindows = null)
    {
        $this->setUpRetries($retries);
    }
}
