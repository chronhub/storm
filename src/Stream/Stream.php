<?php

declare(strict_types=1);

namespace Chronhub\Storm\Stream;

use Generator;

use function count;

final readonly class Stream
{
    private StreamEvents $events;

    public function __construct(public StreamName $streamName, iterable $events = [])
    {
        $this->events = new StreamEvents($events);
    }

    public function name(): StreamName
    {
        return $this->streamName;
    }

    public function events(): Generator
    {
        yield from $this->events->getIterator();

        return count($this->events);
    }
}
