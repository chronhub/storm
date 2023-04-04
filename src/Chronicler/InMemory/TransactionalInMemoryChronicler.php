<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler\InMemory;

use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Chronhub\Storm\Chronicler\Exceptions\TransactionNotStarted;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Chronicler\TransactionalChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalInMemoryChronicler as TransactionalInMemory;
use Chronhub\Storm\Contracts\Stream\StreamCategory;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Generator;
use Illuminate\Support\Collection;
use Throwable;
use function array_merge;
use function iterator_to_array;

final class TransactionalInMemoryChronicler extends AbstractInMemoryChronicler implements TransactionalChronicler, TransactionalInMemory
{
    protected bool $inTransaction = false;

    /**
     * @var array<DomainEvent>
     */
    protected array $unpublishedEvents = [];

    /**
     * @var Collection{StreamName, array<DomainEvent>}
     */
    protected Collection $cachedStreams;

    public function __construct(EventStreamProvider $eventStreamProvider,
                                StreamCategory $streamCategory)
    {
        parent::__construct($eventStreamProvider, $streamCategory);

        $this->cachedStreams = new Collection();
    }

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

        if (! $this->hasStream($streamName) && ! $this->cachedStreams->has($streamName->name)) {
            throw StreamNotFound::withStreamName($streamName);
        }

        $this->storeStreamEvents($streamName->name, $stream->events());
    }

    public function pullUnpublishedEvents(): array
    {
        $unpublishedEvents = $this->unpublishedEvents;

        $this->unpublishedEvents = [];

        return $unpublishedEvents;
    }

    public function beginTransaction(): void
    {
        if ($this->inTransaction) {
            throw new TransactionAlreadyStarted();
        }

        $this->inTransaction = true;
    }

    public function commitTransaction(): void
    {
        if (! $this->inTransaction) {
            throw new TransactionNotStarted();
        }

        $this->cachedStreams->each(
            function (array $streamEvents, string $streamName): void {
                $events = $this->decorateEventWithInternalPosition($streamEvents);

                $this->unpublishedEvents = array_merge($this->unpublishedEvents, $events);

                $stream = [$streamName => $events];

                $this->streams = $this->streams->mergeRecursive($stream);
            }
        );

        $this->cachedStreams = new Collection();

        $this->inTransaction = false;
    }

    public function rollbackTransaction(): void
    {
        if (! $this->inTransaction) {
            throw new TransactionNotStarted();
        }

        $this->cachedStreams = new Collection();

        $this->inTransaction = false;
    }

    public function transactional(callable $callback): bool|array|string|int|float|object
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);

            $this->commitTransaction();
        } catch (Throwable $exception) {
            $this->rollbackTransaction();

            throw $exception;
        }

        return $result ?? true;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function cachedStreams(): Collection
    {
        return $this->cachedStreams;
    }

    public function unpublishedEvents(): array
    {
        return $this->unpublishedEvents;
    }

    private function storeStreamEvents(string $streamName, Generator|Collection $events): void
    {
        if (! $this->inTransaction) {
            throw new TransactionNotStarted();
        }

        $decoratedEvents = $this->decorateEventWithInternalPosition(iterator_to_array($events));

        $stream = [$streamName => $decoratedEvents];

        $this->cachedStreams = $this->cachedStreams->mergeRecursive($stream);
    }
}
