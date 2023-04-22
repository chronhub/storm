<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Contracts\Snapshot\SnapshotStore;

final class InMemorySnapshotStore implements SnapshotStore
{
    /**
     * @var array<class-string, array<string, Snapshot>>|array
     */
    private array $snapshots = [];

    public function get(string $aggregateType, string $aggregateId): ?Snapshot
    {
        return $this->snapshots[$aggregateType][$aggregateId] ?? null;
    }

    public function save(Snapshot ...$snapshots): void
    {
        foreach ($snapshots as $snapshot) {
            $this->snapshots[$snapshot->aggregateType][$snapshot->aggregateId] = $snapshot;
        }
    }

    public function deleteByAggregateType(string $aggregateType): void
    {
        unset($this->snapshots[$aggregateType]);
    }
}
