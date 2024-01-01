<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface GapRecognition
{
    /**
     * Is recoverable if gap is detected.
     */
    public function isRecoverable(): bool;

    /**
     * Check if a gap is detected.
     */
    public function hasGap(): bool;

    /**
     * Check if it has retry when gap is detected.
     */
    public function hasRetry(): bool;

    /**
     * Get retry left.
     */
    public function retryLeft(): int;

    /**
     * Sleep when a gap is detected.
     */
    public function sleep(): void;

    /**
     * Reset gaps detection.
     */
    public function reset(): void;
}
