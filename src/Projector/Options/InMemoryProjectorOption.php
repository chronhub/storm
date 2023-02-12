<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Options;

use Chronhub\Storm\Contracts\Projector\ProjectorOption;

final class InMemoryProjectorOption implements ProjectorOption
{
    use ProvideProjectorOption;

    public function __construct()
    {
        $this->dispatchSignal = false;
        $this->streamCacheSize = 100;
        $this->persistBlockSize = 1;
        $this->lockTimeoutMs = 0;
        $this->sleepBeforeUpdateLock = 100;
        $this->updateLockThreshold = 0;
        $this->retriesMs = [];
        $this->detectionWindows = null;
    }
}
