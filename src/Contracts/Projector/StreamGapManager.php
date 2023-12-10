<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\RuntimeException;

interface StreamGapManager extends StreamManager
{
    /**
     * Sleeps for the internal retry duration available.
     *
     * @throws RuntimeException When no gap is detected
     * @throws RuntimeException When no more retries are available.
     */
    public function sleep(): void;

    /**
     * Check if a gap is detected.
     */
    public function hasGap(): bool;

    /**
     * Check if there is still retry available.
     */
    public function hasRetry(): bool;

    /**
     * Returns the current number of retries.
     *
     * @return int<0,max>
     */
    public function retries(): int;
}
