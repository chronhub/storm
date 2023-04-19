<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler\InMemory;

use Chronhub\Storm\Chronicler\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Chronicler\InMemoryChronicler;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Stream\StreamCategory;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\StreamName;
use Generator;
use Illuminate\Support\Collection;
use function array_map;

abstract class AbstractInMemoryChronicler implements InMemoryChronicler
{
    /**
     * @var Collection{StreamName, array<DomainEvent>}
     */
    protected Collection $streams;

    public function __construct(
        protected readonly EventStreamProvider $eventStreamProvider,
        protected readonly StreamCategory $streamCategory
    ) {
        $this->streams = new Collection();
    }

    public function retrieveAll(StreamName $streamName, AggregateIdentity $aggregateId, string $direction = 'asc'): Generator
    {
        $queryFilter = new RetrieveAllInMemoryQueryFilter($aggregateId, $direction);

        return $this->retrieveFiltered($streamName, $queryFilter);
    }

    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator
    {
        if (! $queryFilter instanceof InMemoryQueryFilter) {
            throw new InvalidArgumentException(
                'Query filter must be an instance of '.InMemoryQueryFilter::class
            );
        }

        return $this->filterEvents($streamName, $queryFilter);
    }

    public function delete(StreamName $streamName): void
    {
        if (! $this->hasStream($streamName)) {
            throw StreamNotFound::withStreamName($streamName);
        }

        $this->eventStreamProvider->deleteStream($streamName->name);

        $this->streams = $this->streams->reject(
            static fn (array $events, string $name): bool => $name === $streamName->name
        );
    }

    public function filterStreamNames(StreamName ...$streamNames): array
    {
        $filteredStreamNames = $this->eventStreamProvider->filterByAscendantStreams($streamNames);

        return array_map(
            static fn (string $streamName): StreamName => new StreamName($streamName),
            $filteredStreamNames
        );
    }

    public function filterCategoryNames(string ...$categoryNames): array
    {
        return $this->eventStreamProvider->filterByAscendantCategories($categoryNames);
    }

    public function hasStream(StreamName $streamName): bool
    {
        return $this->eventStreamProvider->hasRealStreamName($streamName->name);
    }

    public function getStreams(): Collection
    {
        return $this->streams;
    }

    protected function filterEvents(StreamName $streamName, InMemoryQueryFilter $query): Generator
    {
        if (! $this->hasStream($streamName)) {
            throw StreamNotFound::withStreamName($streamName);
        }

        $streamEvents = (new Collection($this->streams->get($streamName->name)))
            ->sortBy(static fn (DomainEvent $event): int => $event->header(EventHeader::AGGREGATE_VERSION), SORT_NUMERIC, 'desc' === $query->orderBy())
            ->filter($query->apply());

        if ($streamEvents->isEmpty()) {
            throw NoStreamEventReturn::withStreamName($streamName);
        }

        yield from $streamEvents;

        return $streamEvents->count();
    }

    /**
     * @param array<DomainEvent> $events
     */
    protected function decorateEventWithInternalPosition(array $events): array
    {
        foreach ($events as &$event) {
            $internalPosition = EventHeader::INTERNAL_POSITION;

            if ($event->hasNot($internalPosition)) {
                $event = $event->withHeader($internalPosition, $event->header(EventHeader::AGGREGATE_VERSION));
            }
        }

        return $events;
    }

    public function getEventStreamProvider(): EventStreamProvider
    {
        return $this->eventStreamProvider;
    }
}
