<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler\InMemory;

use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Stream\Stream;
use Generator;
use Illuminate\Support\Collection;

use function iterator_to_array;

final class StandaloneInMemoryChronicler extends AbstractInMemoryChronicler
{
    public function firstCommit(Stream $stream): void
    {
        $streamName = $stream->name();

        $category = $this->streamCategory->determineFrom($streamName->name);

        if (! $this->eventStreamProvider->createStream($streamName->name, null, $category)) {
            throw StreamAlreadyExists::withStreamName($streamName);
        }

        $this->storeStreamEvents($streamName->name, $stream->events());
    }

    public function amend(Stream $stream): void
    {
        $streamName = $stream->name();

        if (! $this->hasStream($streamName)) {
            throw StreamNotFound::withStreamName($streamName);
        }

        $this->storeStreamEvents($streamName->name, $stream->events());
    }

    private function storeStreamEvents(string $streamName, Generator|Collection $events): void
    {
        $decoratedEvents = $this->decorateEventWithInternalPosition(iterator_to_array($events));

        $this->streams = $this->streams->mergeRecursive([$streamName => $decoratedEvents]);
    }
}
