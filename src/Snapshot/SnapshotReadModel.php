<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Snapshot\SnapshotProvider;
use Chronhub\Storm\Reporter\DomainEvent;

final class SnapshotReadModel implements ReadModel
{
    private array $snapshots = [];

    private bool $isInitialized = false;

    public function __construct(
        private readonly SnapshotProvider $snapshotProvider,
        private readonly array $aggregateTypes
    ) {
    }

    /**
     * @param array<int, DomainEvent> $events
     */
    public function stack(string $operation, ...$events): void
    {
        $this->snapshots[] = $events[0];
    }

    public function persist(): void
    {
        foreach ($this->snapshots as $event) {
            $this->snapshotProvider->store($event);
        }

        $this->snapshots = [];
    }

    public function reset(): void
    {
        foreach ($this->aggregateTypes as $aggregateType) {
            $this->snapshotProvider->deleteAll($aggregateType);
        }
    }

    public function down(): void
    {
        foreach ($this->aggregateTypes as $aggregateType) {
            $this->snapshotProvider->deleteAll($aggregateType);
        }
    }

    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }

    public function initialize(): void
    {
        $this->isInitialized = true;
    }

    public function getSnapshots(): array
    {
        return $this->snapshots;
    }
}
