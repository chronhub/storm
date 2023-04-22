<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Snapshot;

use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;

interface SnapshotQueryScope
{
    public function matchAggregateGreaterThanVersion(
        AggregateIdentity $aggregateId,
        string $aggregateType,
        int $aggregateVersion
    ): QueryFilter;

    public function matchAggregateBetweenIncludedVersion(
        AggregateIdentity $aggregateId,
        int $fromVersion,
        int $toVersion
    ): QueryFilter;
}
