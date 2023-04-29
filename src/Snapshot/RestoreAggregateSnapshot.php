<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateRepositoryWithSnapshotting;
use Chronhub\Storm\Contracts\Aggregate\AggregateRootWithSnapshotting;
use Chronhub\Storm\Contracts\Snapshot\SnapshotQueryScope;
use Generator;
use RuntimeException;

class RestoreAggregateSnapshot
{
    public function __construct(
        protected readonly AggregateRepositoryWithSnapshotting $aggregateRepository,
        protected readonly SnapshotQueryScope $snapshotQueryScope,
    ) {
    }

    /**
     * Restore aggregate from scratch.
     *
     * Stream event loader should either be limited,
     * or use a chunked loader to avoid memory issues
     */
    public function fromScratch(AggregateIdentity $aggregateId, string $aggregateType): ?AggregateRootWithSnapshotting
    {
        try {
            $events = $this->getEventsFromHistory($aggregateId, 1, PHP_INT_MAX);

            /* @var AggregateRootWithSnapshotting $aggregateType */
            return $aggregateType::reconstitute($aggregateId, $events);
        } catch (StreamNotFound) {
            throw new RuntimeException(
                "Unable to take first snapshot for aggregate $aggregateType and id ".$aggregateId->toString()
            );
        }
    }

    /**
     * Restore aggregate from snapshot.
     */
    public function fromSnapshot(Snapshot $lastSnapshot, int $toVersion): ?AggregateRootWithSnapshotting
    {
        $aggregate = $lastSnapshot->aggregateRoot;

        try {
            $events = $this->getEventsFromHistory(
                $aggregate->aggregateId(),
                $lastSnapshot->lastVersion + 1,
                $toVersion + 1
            );

            return $aggregate->reconstituteFromSnapshotting($events);
        } catch (StreamNotFound) {
            return null;
        }
    }

    /**
     * Retrieve history of events from one version to another.
     *
     * @throws StreamNotFound
     */
    protected function getEventsFromHistory(AggregateIdentity $aggregateId, int $fromVersion, int $toVersion): Generator
    {
        $snapshotFilter = $this->snapshotQueryScope->matchAggregateBetweenIncludedVersion(
            $aggregateId, $fromVersion, $toVersion
        );

        return yield from $this->aggregateRepository->retrieveHistory($aggregateId, $snapshotFilter);
    }
}
