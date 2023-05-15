<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Chronhub\Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateCache;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateRepositoryWithSnapshotting;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Aggregate\AggregateRootWithSnapshotting;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Snapshot\SnapshotQueryScope;
use Chronhub\Storm\Contracts\Snapshot\SnapshotStore;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Chronhub\Storm\Snapshot\Snapshot;

final readonly class AggregateSnapshotRepository implements AggregateRepositoryWithSnapshotting
{
    use InteractWithAggregateRepository;

    public function __construct(
        protected Chronicler $chronicler,
        protected StreamProducer $streamProducer,
        protected AggregateCache $aggregateCache,
        protected AggregateType $aggregateType,
        protected AggregateEventReleaser $aggregateReleaser,
        private SnapshotStore $snapshotStore,
        private SnapshotQueryScope $snapshotQueryScope //todo back to snapshot store
    ) {
    }

    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot
    {
        // checkMe when an aggregate is already in cache before snapshot is set
        // could lead to some issues
        if ($this->aggregateCache->has($aggregateId)) {
            return $this->aggregateCache->get($aggregateId);
        }

        $aggregate = $this->reconstituteAggregateWithSnapshot($aggregateId);

        if ($aggregate instanceof AggregateRoot) {
            $this->aggregateCache->put($aggregate);

            return $aggregate;
        }

        $aggregate = $this->reconstituteAggregate($aggregateId);

        if ($aggregate instanceof AggregateRoot) {
            $this->aggregateCache->put($aggregate);
        }

        return $aggregate;
    }

    private function reconstituteAggregateWithSnapshot(AggregateIdentity $aggregateId): ?AggregateRootWithSnapshotting
    {
        // first, get the aggregate from the snapshot store
        $snapshot = $this->snapshotStore->get($this->aggregateType->current(), $aggregateId->toString());

        // if not found, no need to get further
        if (! $snapshot instanceof Snapshot) {
            return null;
        }

        $aggregate = $snapshot->aggregateRoot;

        // next, complete the aggregate with possible new events
        try {
            $snapshotFilter = $this->snapshotQueryScope->matchAggregateGreaterThanVersion(
                $aggregate->aggregateId(), $snapshot->aggregateType, $snapshot->lastVersion
            );

            $streamEvents = $this->retrieveHistory($aggregateId, $snapshotFilter);

            // return an updated aggregate
            return $aggregate->reconstituteFromSnapshotting($streamEvents);
        } catch (NoStreamEventReturn) {
            // no stream event return means that the aggregate is up-to-date
            return $aggregate;
        } catch (StreamNotFound) {
            return null;
        }
    }
}
