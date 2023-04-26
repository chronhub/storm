<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateQueryRepository;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Aggregate\AggregateRootWithSnapshotting;
use Chronhub\Storm\Contracts\Aggregate\AggregateSnapshotQueryRepository;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Snapshot\SnapshotQueryScope;
use Chronhub\Storm\Contracts\Snapshot\SnapshotStore;
use Generator;

final readonly class AggregateSnapshotQuery implements AggregateSnapshotQueryRepository
{
    public function __construct(
        private AggregateType $aggregateType,
        private SnapshotStore $snapshotStore,
        private SnapshotQueryScope $snapshotQueryScope,
        private AggregateQueryRepository $aggregateQuery,
    ) {
    }

    public function retrieve(AggregateIdentity $aggregateId): null|AggregateRoot|AggregateRootWithSnapshotting
    {
        $aggregateRoot = $this->retrieveWithSnapshotting($aggregateId);

        return $aggregateRoot ?? $this->aggregateQuery->retrieve($aggregateId);
    }

    public function retrieveFiltered(AggregateIdentity $aggregateId, QueryFilter $queryFilter): ?AggregateRoot
    {
        return $this->aggregateQuery->retrieveFiltered($aggregateId, $queryFilter);
    }

    public function retrieveHistory(AggregateIdentity $aggregateId, ?QueryFilter $queryFilter): Generator
    {
        return $this->aggregateQuery->retrieveHistory($aggregateId, $queryFilter);
    }

    /**
     * @throws StreamNotFound when stream does not exist
     */
    private function retrieveWithSnapshotting(AggregateIdentity $aggregateId): ?AggregateRootWithSnapshotting
    {
        // first, we try to get the aggregate from the snapshot store
        $snapshot = $this->snapshotStore->get($this->aggregateType->current(), $aggregateId->toString());

        // if not found, no need to get further
        if (! $snapshot instanceof Snapshot) {
            return null;
        }

        $aggregateRoot = $snapshot->aggregateRoot;

        // next, we try to complete the aggregate with possible new events
        try {
            $snapshotFilter = $this->snapshotQueryScope->matchAggregateGreaterThanVersion(
                $aggregateRoot->aggregateId(), $snapshot->aggregateType, $snapshot->lastVersion
            );

            $streamEvents = $this->retrieveHistory($aggregateId, $snapshotFilter);

            // return an updated aggregate
            return $aggregateRoot->reconstituteFromSnapshotting($streamEvents);
        } catch (NoStreamEventReturn) {
            // no stream event return means that the aggregate is up-to-date
            return $aggregateRoot;
        }
    }
}
