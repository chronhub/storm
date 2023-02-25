<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Options;

use Chronhub\Storm\Contracts\Projector\ProjectorOption;

final class InMemoryProjectorOption implements ProjectorOption
{
    use ProvideProjectorOption;

    public function __construct()
    {
        $this->signal = false;
        $this->cacheSize = 100;
        $this->blockSize = 1;
        $this->timeout = 0;
        $this->sleep = 100;
        $this->lockout = 0;
        $this->retries = [];
        $this->detectionWindows = null;
    }
}
