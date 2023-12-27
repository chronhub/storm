<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support;

class AckedStreamObserver
{
    private array $streams = [];

    public function ack(string $streamName): void
    {
        $this->streams[] = $streamName;
    }

    public function reset(): void
    {
        $this->streams = [];
    }

    public function hasStreams(): bool
    {
        return $this->streams !== [];
    }

    public function streams(): array
    {
        return $this->streams;
    }
}
