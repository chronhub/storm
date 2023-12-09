<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

final class EmittedStream
{
    private bool $wasEmitted = false;

    public function wasEmitted(): bool
    {
        return $this->wasEmitted;
    }

    public function emitted(): void
    {
        $this->wasEmitted = true;
    }

    public function reset(): void
    {
        $this->wasEmitted = false;
    }
}
