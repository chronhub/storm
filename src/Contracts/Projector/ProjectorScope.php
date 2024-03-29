<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;

interface ProjectorScope
{
    /**
     * Stop the projection.
     */
    public function stop(): void;

    /**
     * Return the current stream name
     * Only null on setup but available at the first event.
     */
    public function streamName(): ?string;

    /**
     * Return the clock implementation.
     */
    public function clock(): SystemClock;
}
