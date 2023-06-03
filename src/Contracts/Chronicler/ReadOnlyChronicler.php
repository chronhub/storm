<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Chronhub\Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\StreamName;
use Generator;

interface ReadOnlyChronicler
{
    /**
     * Retrieve all events for the given stream and aggregate ID, in the specified direction.
wip     * todo need to bring aggregate type inside method as agId is not sufficient
     *
     * @return Generator{DomainEvent}
     *
     * @throws StreamNotFound|NoStreamEventReturn
     */
    public function retrieveAll(StreamName $streamName, AggregateIdentity $aggregateId, string $direction = 'asc'): Generator;

    /**
     * Retrieve events for the given stream using the given query filter.
     *
     * @return Generator{DomainEvent}
     *
     * @throws StreamNotFound|NoStreamEventReturn
     */
    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator;

    /**
     * Filter stream names by the given stream names in ascending order.
     *
     * @return array{StreamName}
     */
    public function filterStreamNames(StreamName ...$streamNames): array;

    /**
     * Filter category names by the given stream names in ascending order.
     *
     * @return array{string}
     */
    public function filterCategoryNames(string ...$categoryNames): array;

    /**
     * Check if the given stream exists.
     */
    public function hasStream(StreamName $streamName): bool;
}
