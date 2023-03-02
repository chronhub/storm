<?php

declare(strict_types=1);

namespace Chronhub\Storm\Stream;

use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;

final readonly class OneStreamPerAggregate implements StreamProducer
{
    public function __construct(private StreamName $streamName)
    {
    }

    public function toStreamName(AggregateIdentity $aggregateId): StreamName
    {
        return new StreamName($this->streamName->name.'-'.$aggregateId->toString());
    }

    public function toStream(AggregateIdentity $aggregateId, iterable $events = []): Stream
    {
        return new Stream($this->toStreamName($aggregateId), $events);
    }

    public function isFirstCommit(DomainEvent $firstEvent): bool
    {
        return $firstEvent->header(EventHeader::AGGREGATE_VERSION) === 1;
    }

    public function isAutoIncremented(): bool
    {
        return false;
    }
}
