<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Stream;

use Chronhub\Storm\Contracts\Projector\GapRecognition;

class NoopGapDetector implements GapRecognition
{
    public function isRecoverable(): bool
    {
        return false;
    }

    public function hasGap(): bool
    {
        return false;
    }

    public function hasRetry(): bool
    {
        return false;
    }

    public function retryLeft(): int
    {
        return 0;
    }

    public function sleep(): void
    {
    }

    public function reset(): void
    {
    }
}
