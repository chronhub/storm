<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Snapshot;

use Chronhub\Storm\Snapshot\Snapshot;

interface SnapshotStore
{
    /**
     * @param class-string $aggregateType
     */
    public function get(string $aggregateType, string $aggregateId): ?Snapshot;

    public function save(Snapshot ...$snapshots): void;

    /**
     * @param class-string $aggregateType
     */
    public function deleteByAggregateType(string $aggregateType): void;
}
