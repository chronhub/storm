<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Aggregate\AbstractAggregateRepository;
use Chronhub\Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateCache;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateRepositoryWithSnapshotting;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Aggregate\AggregateRootWithSnapshotting;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\ReadOnlyChronicler;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Contracts\Snapshot\SnapshotQueryScope;
use Chronhub\Storm\Contracts\Snapshot\SnapshotStore;
use Chronhub\Storm\Contracts\Stream\StreamProducer;

abstract readonly class AggregateSnapshotRepository extends AbstractAggregateRepository implements AggregateRepositoryWithSnapshotting
{
    public function __construct(
        Chronicler $chronicler,
        StreamProducer $streamProducer,
        AggregateCache $aggregateCache,
        AggregateType $aggregateType,
        MessageDecorator $messageDecorator,
        private SnapshotStore $snapshotStore,
        private SnapshotQueryScope $snapshotQueryScope,
    ) {
        parent::__construct($chronicler, $streamProducer, $aggregateCache, $aggregateType, $messageDecorator);
    }

    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot
    {
        if ($this->aggregateCache->has($aggregateId)) {
            return $this->aggregateCache->get($aggregateId);
        }

        if ($aggregateRoot = $this->retrieveFromSnapshotStore($aggregateId)) {
            $this->aggregateCache->put($aggregateRoot);

            return $aggregateRoot;
        }

        return parent::retrieve($aggregateId);
    }

    public function retrieveFromSnapshotStore(AggregateIdentity $aggregateId): ?AggregateRootWithSnapshotting
    {
        // first, we try to get the aggregate from the snapshot store
        // if not found, we return null
        // next, we try to complete the aggregate by querying
        // the event store from the snapshot last version which
        // return either an updated aggregate or the last known aggregate state
        $snapshot = $this->snapshotStore->get($this->aggregateType->current(), $aggregateId->toString());

        if (! $snapshot instanceof Snapshot) {
            return null;
        }

        $aggregateRoot = $snapshot->aggregateRoot;

        try {
            $streamEvents = $this->fromHistory(
                $aggregateId, $this->snapshotQueryScope->matchAggregateGreaterThanVersion(
                    $aggregateRoot->aggregateId(), $snapshot->aggregateType, $snapshot->lastVersion
                )
            );

            return $aggregateRoot->reconstituteFromSnapshotting($streamEvents);
        } catch (NoStreamEventReturn) {
            return $aggregateRoot;
        } catch (StreamNotFound) {
            return null;
        }
    }

    public function getEventStore(): ReadOnlyChronicler
    {
        return $this->chronicler;
    }

    public function getStreamProducer(): StreamProducer
    {
        return $this->streamProducer;
    }
}
