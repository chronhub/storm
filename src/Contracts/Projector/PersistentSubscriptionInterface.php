<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamGapManager;

interface PersistentSubscriptionInterface extends Subscription
{
    /**
     * Start the persistent subscription and resume the projection.
     */
    public function rise(): void;

    /**
     * Stop the persistent subscription and the projection.
     */
    public function close(): void;

    /**
     * Restart the projection.
     */
    public function restart(): void;

    /**
     * Retrieve the current state and positions of the projection.
     *
     * @throws ProjectionNotFound
     */
    public function refreshDetail(): void;

    /**
     * Get the current status of the projection.
     */
    public function disclose(): ProjectionStatus;

    /**
     * Persist the current state and positions of the projection.
     */
    public function store(): void;

    /**
     * Persist the current state and positions of the projection
     * when threshold is reached.
     */
    public function persistWhenThresholdIsReached(): void;

    /**
     * Reset the state and position of the projection.
     */
    public function revise(): void;

    /**
     * Delete the projection with or without emitted events.
     */
    public function discard(bool $withEmittedEvents): void;

    /**
     * Update projection lock and stream positions.
     */
    public function renew(): void;

    /**
     * Release the projection lock.
     */
    public function freed(): void;

    /**
     * Get the projection name.
     */
    public function projectionName(): string;

    /**
     * Get the event counter instance.
     */
    public function eventCounter(): EventCounter;

    /**
     * Get the stream gap detector instance.
     */
    public function gap(): StreamGapManager;
}
