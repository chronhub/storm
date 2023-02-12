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
     *
     * @return bool
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
     *
     * @return bool
     */
    public function stop(): bool;

    /**
     * Restart projection
     *
     * @return bool
     */
    public function startAgain(): bool;

    /**
     * Load projection status
     *
     * @return ProjectionStatus
     */
    public function loadStatus(): ProjectionStatus;

    /**
     * Persist domain events handled
     *
     * @return bool
     */
    public function persist(): bool;

    /**
     * Reset projection
     *
     * @return bool
     */
    public function reset(): bool;

    /**
     * Delete projection with or without emitted events
     *
     * @param  bool  $withEmittedEvents
     * @return bool
     */
    public function delete(bool $withEmittedEvents): bool;

    /**
     * Check if projection already exists
     *
     * @return bool
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
     *
     * @return bool
     */
    public function updateLock(): bool;

    /**
     * Release projection lock
     *
     * @return bool
     */
    public function releaseLock(): bool;

    /**
     * Get the current projection stream name
     *
     * @return string
     */
    public function currentStreamName(): string;
}
