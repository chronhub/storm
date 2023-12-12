<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;

interface Management
{
    /**
     * Get the clock instance.
     */
    public function getClock(): SystemClock;

    /**
     * Get the current stream name.
     */
    public function getCurrentStreamName(): string;
}