<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Options;

use Chronhub\Storm\Contracts\Projector\ProjectionOptionImmutable;

final class InMemoryOptionFixed implements ProjectionOptionImmutable
{
    use ProvideOption;

    public function __construct()
    {
        $this->signal = false;
        $this->cacheSize = 100;
        $this->blockSize = 1;
        $this->sleep = 100;
        $this->timeout = 1;
        $this->lockout = 0;
        $this->retries = [];
        $this->detectionWindows = null;
    }
}
