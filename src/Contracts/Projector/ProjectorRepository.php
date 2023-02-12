<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;

interface ProjectorRepository
{
    /**
     * Start projection
     *
     * @return void
     */
    public function rise(): void;

    /**
     * Stop projection
     *
     * @return void
     */
    public function close(): void;

    /**
     * Restart projection
     *
     * available when resetting projection
     *
     * @return void
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
     *
     * @return ProjectionStatus
     */
    public function disclose(): ProjectionStatus;

    /**
     * Persist domain handled by the projection
     *
     * @return void
     */
    public function store(): void;

    /**
     * Reset projection
     *
     * @return void
     */
    public function revise(): void;

    /**
     * Delete projection with or without emitted events
     *
     * @param  bool  $withEmittedEvents
     * @return void
     */
    public function discard(bool $withEmittedEvents): void;

    /**
     * Update projection lock
     *
     * @return void
     */
    public function renew(): void;

    /**
     * Release projection lock when possible
     *
     * @return void
     */
    public function freed(): void;

    /**
     * Get the current projection stream name
     *
     * @return string
     */
    public function streamName(): string;
}
