<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateRootWithSnapshotting;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Snapshot\SnapshotProvider;
use Chronhub\Storm\Contracts\Snapshot\SnapshotStore;
use Chronhub\Storm\Reporter\DomainEvent;

final class VersioningSnapshotProvider implements SnapshotProvider
{
    use ExtractAggregateIdFromHeader;

    public function __construct(
        private readonly SnapshotStore $snapshotStore,
        private readonly RestoreAggregateSnapshot $restoreAggregate,
        private readonly SystemClock $clock,
        public readonly int $persistEveryVersion = 1000
    ) {
    }

    public function store(DomainEvent $event): void
    {
        $aggregateId = $this->extractAggregateId($event);
        $aggregateType = $event->header(EventHeader::AGGREGATE_TYPE);
        $aggregateVersion = (int) $event->header(EventHeader::AGGREGATE_VERSION);

        $aggregateRoot = $this->reconstituteAggregate($aggregateId, $aggregateType, $aggregateVersion);

        if ($aggregateRoot) {
            $snapshot = new Snapshot(
                $aggregateType,
                $aggregateId->toString(),
                $aggregateRoot,
                $aggregateRoot->version(),
                $this->clock->now()
            );

            $this->snapshotStore->save($snapshot);
        }
    }

    public function deleteAll(string $aggregateType): void
    {
        $this->snapshotStore->deleteByAggregateType($aggregateType);
    }

    private function reconstituteAggregate(AggregateIdentity $aggregateId, string $aggregateType, int $eventVersion): ?AggregateRootWithSnapshotting
    {
        // If we haven't reached the threshold for persisting a snapshot yet, return null
        if ($this->persistEveryVersion > $eventVersion) {
            return null;
        }

        // Try to get the last snapshot for the aggregate from the snapshot store
        $lastSnapshot = $this->snapshotStore->get($aggregateType, $aggregateId->toString());

        // If there is no snapshot, create a new aggregate and reconstitute it from history
        if (! $lastSnapshot instanceof Snapshot) {
            return $this->restoreAggregate->fromScratch($aggregateId, $aggregateType);
        }

        // If the difference between the current version and the last version in the snapshot
        // is lower than the persistEveryVersion threshold, return null.
        if (($this->persistEveryVersion + $lastSnapshot->lastVersion) > $eventVersion) {
            return null;
        }

        // Reconstitute the aggregate from the last snapshot and the events that have occurred since then
        // using $version + $this->persistEveryVersion as the upper limit for the events to retrieve
        return $this->restoreAggregate->fromSnapshot($lastSnapshot, $eventVersion);
    }
}
