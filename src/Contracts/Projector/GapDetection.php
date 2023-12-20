<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface GapDetection
{
    /**
     * Check if a gap is detected.
     */
    public function hasGap(): bool;

    public function sleepWhenGap(): void;
}
