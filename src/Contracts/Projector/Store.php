<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;

interface Store
{
    /**
     * Create projection
     */
    public function create(): bool;

    /**
     * load projection state
     *
     * @throws ProjectionNotFound
     */
    public function loadState(): bool;

    /**
     * Stop projection
     */
    public function stop(): bool;

    /**
     * Restart projection
     */
    public function startAgain(): bool;

    /**
     * Load projection status
     */
    public function loadStatus(): ProjectionStatus;

    /**
     * Persist domain events handled
     */
    public function persist(): bool;

    /**
     * Reset projection
     */
    public function reset(): bool;

    /**
     * Delete projection with or without emitted events
     */
    public function delete(bool $withEmittedEvents): bool;

    /**
     * Check if projection already exists
     */
    public function exists(): bool;

    /**
     * Acquire projection lock
     *
     * @throws ProjectionAlreadyRunning
     */
    public function acquireLock(): bool;

    /**
     * Update projection lock
     */
    public function updateLock(): bool;

    /**
     * Release projection lock
     */
    public function releaseLock(): bool;

    /**
     * Get the current projection stream name
     */
    public function currentStreamName(): string;
}
