<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Snapshot;

use Chronhub\Storm\Reporter\DomainEvent;

interface SnapshotProvider
{
    public function store(DomainEvent $event): void;

    /**
     * @param class-string $aggregateType
     */
    public function deleteAll(string $aggregateType): void;
}
