<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Iterator;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Reporter\DomainEvent;
use Countable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Collection;
use Iterator;

use function log;
use function max;

final class MergeStreamIterator implements Countable, Iterator
{
    private const CHUNK_SIZE = 32;

    /**
     * @var Collection<array{StreamIterator,string}>
     */
    private Collection $iterators;

    private Collection $originalIteratorOrder;

    private int $numberOfIterators;

    public function __construct(array $streamNames, StreamIterator ...$iterators)
    {
        $this->iterators = collect($iterators)->map(fn (StreamIterator $iterator, int $key): array => [$iterator, $streamNames[$key]]);

        $this->originalIteratorOrder = $this->iterators;

        $this->numberOfIterators = $this->iterators->count();

        $this->prioritizeIterators();
    }

    public function rewind(): void
    {
        $this->iterators->each(function (array $iterator): void {
            $iterator[0]->rewind();
        });

        $this->prioritizeIterators();
    }

    public function valid(): bool
    {
        return $this->iterators->contains(fn (array $iterator): bool => $iterator[0]->valid());
    }

    public function next(): void
    {
        // advance the prioritized iterator
        $this->iterators->first()[0]->next();

        $this->prioritizeIterators();
    }

    public function current(): ?DomainEvent
    {
        return $this->iterators->first()[0]->current();
    }

    public function streamName(): string
    {
        return $this->iterators->first()[1];
    }

    public function key(): int
    {
        return $this->iterators->first()[0]->key();
    }

    public function count(): int
    {
        return $this->iterators->sum(fn (array $iterator) => $iterator[0]->count());
    }

    private function prioritizeIterators(): void
    {
        if ($this->numberOfIterators > 1) {
            $iterators = $this->originalIteratorOrder
                ->filter(fn (array $iterator): bool => $iterator[0]->valid())
                ->sortBy(fn (array $iterator): DateTimeImmutable => $this->toDatetime($iterator[0]->current()));

            $chunkSize = $this->calculateDynamicChunkSize($iterators);

            $this->iterators = $iterators->chunk($chunkSize)->flatten(1);
        }
    }

    private function toDatetime(DomainEvent $event): DateTimeImmutable
    {
        // todo bring Clock or use facade
        $eventTime = $event->header(Header::EVENT_TIME);

        if ($eventTime instanceof DateTimeImmutable) {
            return $eventTime;
        }

        return new DateTimeImmutable($eventTime, new DateTimeZone('UTC'));
    }

    /**
     * Determine a chunk size based on the total number of events.
     */
    private function calculateDynamicChunkSize(Collection $iterators): int
    {
        $totalEvents = $iterators->sum(fn (array $iterator): int => $iterator[0]->count());

        $chunkSize = max(self::CHUNK_SIZE, (int) log($totalEvents, 2));

        while ($totalEvents > $chunkSize * 4) {
            $chunkSize *= 2;
        }

        return $chunkSize;
    }
}