<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use function in_array;

class AckedStreamWatcher
{
    protected array $streams = [];

    public function ack(string $streamName): void
    {
        if (! in_array($streamName, $this->streams)) {
            $this->streams[] = $streamName;
        }
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
