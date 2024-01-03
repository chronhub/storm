<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Options;

use Chronhub\Storm\Contracts\Projector\ProjectionOption;

final class DefaultOption implements ProjectionOption
{
    use ProvideOption;

    public function __construct(
        protected readonly bool $signal = false,
        protected readonly int $cacheSize = 1000,
        protected readonly int $blockSize = 1000,
        protected readonly array $sleep = [1, 5], // one query with a sleep time of 0.2
        protected readonly int $timeout = 10000,
        protected readonly int $lockout = 1000000,
        protected readonly int $loadLimiter = 1000,
        protected readonly bool $onlyOnceDiscovery = false,
        protected readonly array $snapshotInterval = ['position' => 1000, 'time' => null, 'usleep' => null],
        array|string $retries = [0, 5, 10, 25, 50, 100, 150, 200, 250, 300, 350, 400, 450, 500],
        protected readonly ?string $detectionWindows = null
    ) {
        $this->setUpRetries($retries);
    }
}
