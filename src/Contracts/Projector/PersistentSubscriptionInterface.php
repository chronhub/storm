<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Activity\MonitorRemoteStatus;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\EventCounter;

interface PersistentSubscriptionInterface extends Subscription
{
    /**
     * Start the persistent subscription and resume the projection.
     */
    public function rise(): void;

    /**
     * Stop the projection.
     */
    public function close(): void;

    /**
     * Restart the projection.
     */
    public function restart(): void;

    /**
     * Synchronize the current state and positions of the projection,
     * on rising or stopping projection (discover status) during the first run.
     *
     * @throws ProjectionNotFound
     *
     *@see MonitorRemoteStatus
     */
    public function synchronise(): void;

    /**
     * Get the current status of the projection.
     */
    public function disclose(): ProjectionStatus;

    /**
     * Persist the current projection.
     */
    public function store(): void;

    /**
     * Update lock if it can be refreshed.
     */
    public function update(): void;

    /**
     * Persist the current projection when the threshold is reached.
     *
     * @see ProjectionOption::BLOCK_SIZE
     */
    public function persistWhenCounterIsReached(): void;

    /**
     * Reset the projection.
     */
    public function revise(): void;

    /**
     * Delete the projection with or without emitted events.
     */
    public function discard(bool $withEmittedEvents): void;

    /**
     * Release the projection lock.
     */
    public function freed(): void;

    /**
     * Get the projection name.
     */
    public function getName(): string;

    /**
     * Get the event counter instance.
     */
    public function eventCounter(): EventCounter;
}
