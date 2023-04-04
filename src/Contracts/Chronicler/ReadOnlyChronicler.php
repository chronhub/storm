<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\StreamName;
use Generator;

interface ReadOnlyChronicler
{
    /**
     * @return Generator{DomainEvent}
     *
     * @throws StreamNotFound
     */
    public function retrieveAll(StreamName $streamName, AggregateIdentity $aggregateId, string $direction = 'asc'): Generator;

    /**
     * @return Generator{DomainEvent}
     *
     * @throws StreamNotFound
     */
    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator;

    /**
     * @return array{StreamName}
     */
    public function filterStreamNames(StreamName ...$streamNames): array;

    /**
     * @return array{string}
     */
    public function filterCategoryNames(string ...$categoryNames): array;

    public function hasStream(StreamName $streamName): bool;
}
