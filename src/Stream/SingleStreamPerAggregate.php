<?php

declare(strict_types=1);

namespace Chronhub\Storm\Stream;

use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Chronhub\Storm\Reporter\DomainEvent;

final readonly class SingleStreamPerAggregate implements StreamProducer
{
    public function __construct(private StreamName $streamName)
    {
    }

    public function toStreamName(AggregateIdentity $aggregateId): StreamName
    {
        return $this->streamName;
    }

    public function toStream(AggregateIdentity $aggregateId, iterable $events = []): Stream
    {
        return new Stream($this->streamName, $events);
    }

    public function isFirstCommit(DomainEvent $firstEvent): bool
    {
        return false;
    }

    public function isAutoIncremented(): bool
    {
        return true;
    }
}