<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Generator;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;

interface ReadOnlyChronicler
{
    /**
     * Retrieve all stream events with order
     *
     * @return Generator<DomainEvent>
     *
     * @throws StreamNotFound
     */
    public function retrieveAll(StreamName $streamName, AggregateIdentity $aggregateId, string $direction = 'asc'): Generator;

    /**
     * @return Generator<DomainEvent>
     *
     * @throws StreamNotFound
     */
    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator;

    /**
     * Get stream names
     *
     * @return array<StreamName>
     */
    public function filterStreamNames(StreamName ...$streamNames): array;

    /**
     * Get category names
     *
     * @return array<string>
     */
    public function filterCategoryNames(string ...$categoryNames): array;

    /**
     * Check if stream name exists
     */
    public function hasStream(StreamName $streamName): bool;
}
