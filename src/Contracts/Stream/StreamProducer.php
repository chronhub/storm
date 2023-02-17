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
     */
    public function toStreamName(AggregateIdentity $aggregateId): StreamName;

    /**
     * Produce new stream instance
     */
    public function toStream(AggregateIdentity $aggregateId, iterable $events = []): Stream;

    /**
     * Check if domain event given is first commit
     */
    public function isFirstCommit(DomainEvent $firstEvent): bool;

    /**
     * Check if producer strategy handle auto incrementation of sequence no
     */
    public function isAutoIncremented(): bool;
}
