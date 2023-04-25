<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateQueryRepository;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Generator;

final readonly class AggregateQuery implements AggregateQueryRepository
{
    public function __construct(
        private Chronicler $chronicler,
        private StreamProducer $streamProducer,
        private AggregateType $aggregateType,
    ) {
    }

    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot
    {
        return $this->reconstituteAggregate($aggregateId);
    }

    public function retrieveFiltered(AggregateIdentity $aggregateId, QueryFilter $queryFilter): ?AggregateRoot
    {
        return $this->reconstituteAggregate($aggregateId, $queryFilter);
    }

    public function retrieveHistory(AggregateIdentity $aggregateId, ?QueryFilter $queryFilter): Generator
    {
        $streamName = $this->streamProducer->toStreamName($aggregateId);

        if ($queryFilter instanceof QueryFilter) {
            return $this->chronicler->retrieveFiltered($streamName, $queryFilter);
        }

        return $this->chronicler->retrieveAll($streamName, $aggregateId);
    }

    private function reconstituteAggregate(AggregateIdentity $aggregateId, ?QueryFilter $queryFilter = null): ?AggregateRoot
    {
        try {
            $history = $this->retrieveHistory($aggregateId, $queryFilter);

            if (! $history->valid()) {
                return null;
            }

            /** @var AggregateRoot $aggregate */
            $aggregate = $this->aggregateType->from($history->current());

            return $aggregate::reconstitute($aggregateId, $history);
        } catch (StreamNotFound) {
            return null;
        }
    }
}
