<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Options;

use Chronhub\Storm\Contracts\Projector\ProjectorOption;

final class DefaultProjectorOption implements ProjectorOption
{
    use ProvideProjectorOption;

    public function __construct(
        public readonly bool $dispatchSignal = false,
        public readonly int $streamCacheSize = 1000,
        public readonly int $lockTimeoutMs = 1000,
        public readonly int $sleepBeforeUpdateLock = 100000,
        public readonly int $persistBlockSize = 1000,
        public readonly int $updateLockThreshold = 100000,
        array|string $retriesMs = [0, 5, 50, 100, 150, 200, 250],
        public readonly ?string $detectionWindows = null)
    {
        $this->setUpRetriesMs($retriesMs);
    }
}
