<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;

use function array_diff;
use function count;

class EventStreamWatcher
{
    protected array $eventStreams = [];

    protected array $newEventStreams = [];

    public function __construct(protected readonly EventStreamProvider $eventStreamProvider)
    {
    }

    public function discover(callable $query): array
    {
        $eventStreams = $query($this->eventStreamProvider);

        $newEventStreams = array_diff($eventStreams, $this->eventStreams);

        $this->eventStreams = $eventStreams;
        $this->newEventStreams = $newEventStreams;

        return $eventStreams;
    }

    public function hasEventStreams(): bool
    {
        return count($this->eventStreams) > 0;
    }

    public function newEventStreams(): array
    {
        return $this->newEventStreams;
    }

    public function resetNewEventStreams(): void
    {
        $this->newEventStreams = [];
    }
}
