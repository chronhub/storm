<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Snapshot\SnapshotQueryScope;

class InMemorySnapshotQueryScope implements SnapshotQueryScope
{
    public function matchAggregateGreaterThanVersion(AggregateIdentity $aggregateId, string $aggregateType, int $aggregateVersion): InMemoryQueryFilter
    {
        return new MatchAggregateGreaterThanVersion($aggregateId, $aggregateType, $aggregateVersion);
    }

    public function matchAggregateBetweenIncludedVersion(AggregateIdentity $aggregateId, int $fromVersion, int $toVersion): InMemoryQueryFilter
    {
        return new MatchAggregateBetweenIncludedVersion($aggregateId, $fromVersion, $toVersion);
    }
}
