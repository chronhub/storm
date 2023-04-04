<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Reporter\DomainEvent;
use Generator;

trait ReconstituteAggregate
{
    protected function reconstituteAggregateRoot(
        AggregateIdentity $aggregateId,
        ?QueryFilter $queryFilter = null
    ): ?AggregateRoot {
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
     * @return Generator{DomainEvent}
     *
     * @throws StreamNotFound
     */
    protected function fromHistory(
        AggregateIdentity $aggregateId,
        ?QueryFilter $queryFilter
    ): Generator {
        $streamName = $this->streamProducer->toStreamName($aggregateId);

        if ($queryFilter) {
            return $this->chronicler->retrieveFiltered($streamName, $queryFilter);
        }

        return $this->chronicler->retrieveAll($streamName, $aggregateId);
    }
}
