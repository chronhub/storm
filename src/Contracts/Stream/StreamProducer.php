<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Stream;

use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;

interface StreamProducer
{
    /**
     * Produce stream name depends on strategy used
     *
     * @param  AggregateIdentity  $aggregateId
     * @return StreamName
     */
    public function toStreamName(AggregateIdentity $aggregateId): StreamName;

    /**
     * Produce new stream instance
     *
     * @param  AggregateIdentity  $aggregateId
     * @param  iterable  $events
     * @return Stream
     */
    public function toStream(AggregateIdentity $aggregateId, iterable $events = []): Stream;

    /**
     * Check if domain event given is first commit
     *
     * @param  DomainEvent  $firstEvent
     * @return bool
     */
    public function isFirstCommit(DomainEvent $firstEvent): bool;

    /**
     * Check if producer strategy handle auto incrementation of sequence no
     *
     * @return bool
     */
    public function isAutoIncremented(): bool;
}
