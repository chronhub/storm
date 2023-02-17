<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;

interface ProjectorRepository
{
    /**
     * Start projection
     */
    public function rise(): void;

    /**
     * Stop projection
     */
    public function close(): void;

    /**
     * Restart projection
     *
     * available when resetting projection
     */
    public function restart(): void;

    /**
     * Store the in memory state remotely
     *
     * @throws ProjectionNotFound
     */
    public function boundState(): void;

    /**
     * Discover projection status
     */
    public function disclose(): ProjectionStatus;

    /**
     * Persist domain handled by the projection
     */
    public function store(): void;

    /**
     * Reset projection
     */
    public function revise(): void;

    /**
     * Delete projection with or without emitted events
     */
    public function discard(bool $withEmittedEvents): void;

    /**
     * Update projection lock
     */
    public function renew(): void;

    /**
     * Release projection lock when possible
     */
    public function freed(): void;

    /**
     * Get the current projection stream name
     */
    public function streamName(): string;
}
