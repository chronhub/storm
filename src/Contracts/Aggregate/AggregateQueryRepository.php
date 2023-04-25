<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

use Chronhub\Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Reporter\DomainEvent;
use Generator;

interface AggregateQueryRepository
{
    /**
     * Retrieve the last valid state of aggregate.
     *
     * Depends on configuration, it can be cached.
     */
    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot;

    /**
     * Retrieve aggregate depends on query filter.
     *
     * Note that it should return a valid state of aggregate,
     * but it also can return incomplete or invalid aggregate depending on query filter.
     * so it will never be cached.
     */
    public function retrieveFiltered(AggregateIdentity $aggregateId, QueryFilter $queryFilter): ?AggregateRoot;

    /**
     * Retrieve history of events for aggregate.
     *
     * @return Generator{DomainEvent}
     *
     * @throws StreamNotFound      when stream does not exist
     * @throws NoStreamEventReturn when stream does not contain any event
     */
    public function retrieveHistory(AggregateIdentity $aggregateId, ?QueryFilter $queryFilter): Generator;
}
