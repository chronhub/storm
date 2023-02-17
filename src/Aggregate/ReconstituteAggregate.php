<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Generator;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;

trait ReconstituteAggregate
{
    /**
     * Reconstitute aggregate root from his aggregate id and conditionally from a query filter
     */
    protected function reconstituteAggregateRoot(AggregateIdentity $aggregateId, ?QueryFilter $queryFilter = null): ?AggregateRoot
    {
        try {
            $history = $this->fromHistory($aggregateId, $queryFilter);

            if (! $history->valid()) {
                return null;
            }

            /** @var AggregateRoot $aggregateRoot */
            $aggregateRoot = $this->aggregateType->from($history->current());

            return $aggregateRoot::reconstitute($aggregateId, $history);
        } catch (StreamNotFound) {
            return null;
        }
    }

    /**
     * Retrieve aggregate root events history
     *
     * @return Generator<DomainEvent>
     *
     * @throws StreamNotFound
     */
    protected function fromHistory(AggregateIdentity $aggregateId, ?QueryFilter $queryFilter): Generator
    {
        $streamName = $this->streamProducer->toStreamName($aggregateId);

        if ($queryFilter) {
            return $this->chronicler->retrieveFiltered($streamName, $queryFilter);
        }

        return $this->chronicler->retrieveAll($streamName, $aggregateId);
    }
}
