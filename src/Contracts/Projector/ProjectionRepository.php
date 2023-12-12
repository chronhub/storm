<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyExists;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionDetail;

interface ProjectionRepository
{
    /**
     * Creates a new projection with the given status.
     *
     * @throws ProjectionAlreadyExists When a projection with the given name already exists.
     */
    public function create(ProjectionStatus $status): void;

    /**
     * Starts the projection by acquiring the lock.
     *
     * @throws ProjectionAlreadyRunning When another projection has already acquired the lock.
     * @throws ProjectionFailed         When the lock cannot be acquired.
     */
    public function start(ProjectionStatus $projectionStatus): void;

    /**
     * Persists projection data
     *
     * @throws ProjectionFailed When projection data cannot be stored.
     */
    public function persist(ProjectionDetail $projectionDetail): void;

    /**
     * Stops the projection and store data.
     *
     * @throws ProjectionNotFound When a projection with the given name doesn't exist.
     * @throws ProjectionFailed   When projection data cannot be stored.
     */
    public function stop(ProjectionDetail $projectionDetail, ProjectionStatus $projectionStatus): void;

    /**
     * Starts the projection again.
     *
     * @throws ProjectionNotFound When a projection with the given name doesn't exist.
     * @throws ProjectionFailed   When projection failed to update data.
     */
    public function startAgain(ProjectionStatus $projectionStatus): void;

    /**
     * Resets projection data.
     */
    public function reset(ProjectionDetail $projectionDetail, ProjectionStatus $currentStatus): void;

    /**
     * Deletes the projection.
     *
     * @param bool $withEmittedEvents only use as a flag when dispatching internal event
     *
     * @throws ProjectionNotFound When a projection with the given name doesn't exist.
     * @throws ProjectionFailed   When projection data cannot be deleted.
     */
    public function delete(bool $withEmittedEvents): void;

    /**
     * Update lock when the lock threshold is reached
     */
    public function updateLock(): void;

    /**
     * Releases the lock for the projection.
     */
    public function release(): void;

    /**
     * Loads the projection status.
     */
    public function loadStatus(): ProjectionStatus;

    /**
     * Loads the projection state and stream positions.
     *
     * @throws ProjectionNotFound When the projection doesn't exist.
     */
    public function loadDetail(): ProjectionDetail;

    /**
     * Checks if the projection exists.
     */
    public function exists(): bool;

    /**
     * Returns the name of the projection.
     *
     * @return non-empty-string
     */
    public function projectionName(): string;
}
