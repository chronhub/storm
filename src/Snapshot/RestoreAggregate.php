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

class RestoreAggregate
{
    public function __construct(
        protected readonly AggregateRepositoryWithSnapshotting $aggregateRepository,
        protected readonly SnapshotQueryScope $snapshotQueryScope,
    ) {
    }

    public function fromScratch(AggregateIdentity $aggregateId, string $aggregateType): ?AggregateRootWithSnapshotting
    {
        try {
            // Stream event loader should either be limited
            // or use the chunked loader to avoid memory issues
            $events = $this->getEventsFromHistory($aggregateId, 1, PHP_INT_MAX);

            /* @var AggregateRootWithSnapshotting $aggregateType */
            return $aggregateType::reconstitute($aggregateId, $events);
        } catch (StreamNotFound) {
            throw new RuntimeException(
                "Unable to take first snapshot for aggregate $aggregateType and id ".$aggregateId->toString()
            );
        }
    }

    public function fromSnapshot(Snapshot $lastSnapshot, int $eventVersion): ?AggregateRootWithSnapshotting
    {
        $aggregate = $lastSnapshot->aggregateRoot;

        try {
            $events = $this->getEventsFromHistory(
                $aggregate->aggregateId(),
                $lastSnapshot->lastVersion + 1,
                $eventVersion + 1 //todo try PHP_INT_MAX
            );

            return $aggregate->reconstituteFromSnapshotting($events);
        } catch (StreamNotFound) {
            return null;
        }
    }

    private function getEventsFromHistory(AggregateIdentity $aggregateId, int $fromVersion, int $toVersion): Generator
    {
        $filter = $this->snapshotQueryScope->matchAggregateBetweenIncludedVersion($aggregateId, $fromVersion, $toVersion);

        $streamName = $this->aggregateRepository->getStreamProducer()->toStreamName($aggregateId);

        return yield from $this->aggregateRepository->getEventStore()->retrieveFiltered($streamName, $filter);
    }
}
