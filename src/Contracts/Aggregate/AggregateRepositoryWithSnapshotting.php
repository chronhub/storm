<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

interface AggregateRepositoryWithSnapshotting extends AggregateRepository
{
    // not used
    // we could have a method in aggregate repository to assert snapshot is supported
}
