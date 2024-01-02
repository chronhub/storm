<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\Stream\Checkpoint;

interface PersistentManagement extends Management
{
    /**
     * Start the persistent subscription and resume the projection.
     */
    public function rise(): void;

    /**
     * Restart the projection.
     */
    public function restart(): void;

    /**
     * Synchronize the current state and positions of the projection.
     *
     * @throws ProjectionNotFound
     */
    public function synchronise(): void;

    /**
     * Set the current status of the projection.
     */
    public function disclose(): void;

    /**
     * Persist the current projection.
     */
    public function store(): void;

    /**
     * Update lock if it can be refreshed.
     */
    public function tryUpdateLock(): void;

    /**
     * Persist the current projection when the threshold is reached.
     *
     * @see ProjectionOption::BLOCK_SIZE
     */
    public function persistWhenThresholdIsReached(): void;

    /**
     * Reset the projection.
     * Note it also deletes all snapshots taken for the current projection.
     */
    public function revise(): void;

    /**
     * Delete the projection with or without emitted events.
     * Note it also deletes all snapshots taken for the current projection.
     */
    public function discard(bool $withEmittedEvents): void;

    /**
     * Release the projection lock.
     */
    public function freed(): void;

    /**
     * Take a snapshot of the projection.
     */
    public function snapshot(Checkpoint $checkpoint): void;

    /**
     * Get the projection name (aka stream name).
     */
    public function getName(): string;
}
