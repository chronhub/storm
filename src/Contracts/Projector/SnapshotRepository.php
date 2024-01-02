<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Stream\Checkpoint;

interface SnapshotRepository
{
    /**
     * Take a snapshot of the projection.
     */
    public function snapshot(string $projectionName, Checkpoint $checkpoint): void;

    /**
     * Delete snapshot by projection name.
     */
    public function deleteByProjectionName(string $projectionName): void;

    /**
     * Check if projection can take a snapshot.
     */
    public function shouldSnapshot(string $projectionName, Checkpoint $checkpoint): bool;
}
