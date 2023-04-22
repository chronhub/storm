<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

use Chronhub\Storm\Contracts\Chronicler\ReadOnlyChronicler;
use Chronhub\Storm\Contracts\Stream\StreamProducer;

interface AggregateRepositoryWithSnapshotting extends AggregateRepository
{
    public function retrieveFromSnapshotStore(AggregateIdentity $aggregateId): ?AggregateRootWithSnapshotting;

    public function getEventStore(): ReadOnlyChronicler;

    public function getStreamProducer(): StreamProducer;
}
