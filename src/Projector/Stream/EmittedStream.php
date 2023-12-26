<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Stream;

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

    public function unlink(): void
    {
        $this->wasEmitted = false;
    }
}
