<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Iterator;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Reporter\DomainEvent;
use Countable;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Iterator;

final class MergeStreamIterator implements Countable, Iterator
{
    /**
     * @var Collection<array{StreamIterator,string}>
     */
    private Collection $iterators;

    private Collection $originalIteratorOrder;

    public readonly int $numberOfIterators;

    public readonly int $numberOfEvents;

    public function __construct(private readonly SystemClock $clock, Collection $iterators)
    {
        $this->iterators = $iterators;
        $this->originalIteratorOrder = $this->iterators;
        $this->numberOfIterators = $this->iterators->count();
        $this->numberOfEvents = $this->iterators->sum(fn (array $stream) => $stream[0]->count());

        $this->prioritizeIterators();
    }

    public function rewind(): void
    {
        $this->iterators->each(function (array $stream): void {
            $stream[0]->rewind();
        });

        $this->prioritizeIterators();
    }

    public function valid(): bool
    {
        return $this->iterators->contains(fn (array $stream): bool => $stream[0]->valid());
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

    /**
     * @phpstan-impure
     */
    public function count(): int
    {
        return $this->iterators->sum(fn (array $stream): int => $stream[0]->count());
    }

    private function prioritizeIterators(): void
    {
        if ($this->numberOfIterators > 1) {
            $this->iterators = $this->originalIteratorOrder
                ->filter(fn (array $stream): bool => $stream[0]->valid())
                ->sortBy(fn (array $stream): DateTimeImmutable => $this->toDatetime($stream[0]->current()));
        }
    }

    private function toDatetime(DomainEvent $event): DateTimeImmutable
    {
        return $this->clock->toPointInTime($event->header(Header::EVENT_TIME));
    }
}
