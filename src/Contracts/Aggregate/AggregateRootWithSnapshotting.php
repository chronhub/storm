<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

use Chronhub\Storm\Reporter\DomainEvent;
use Generator;

interface AggregateRootWithSnapshotting extends AggregateRoot
{
    /**
     * @param Generator<DomainEvent> $events
     */
    public function reconstituteFromSnapshotting(Generator $events): ?static;
}
