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
     * Check if gap is detected.
     */
    public function hasGap(): bool;

    /**
     * Check if has retry when gap is detected.
     */
    public function hasRetry(): bool;

    /**
     * Sleep when gap is detected.
     */
    public function sleep(): void;

    /**
     * Reset gaps detected.
     */
    public function reset(): void;
}
