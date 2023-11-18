<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionDetail;

interface ProjectionRepositoryInterface
{
    /**
     * Creates a new projection with the given status.
     *
     * @param  ProjectionStatus $status The projection status.
     * @return bool             True if the operation was successful, false otherwise.
     */
    public function create(ProjectionStatus $status): bool;

    /**
     * Stops the projection and saves the stream positions and state.
     */
    public function stop(ProjectionDetail $projectionDetail): bool;

    /**
     * Starts the projection again after it has been stopped or reset.
     *
     * @return bool True if the operation was successful, false otherwise.
     */
    public function startAgain(): bool;

    /**
     * Saves the stream positions and state.
     */
    public function persist(ProjectionDetail $projectionDetail): bool;

    /**
     * Resets stream positions and state.
     */
    public function reset(ProjectionDetail $projectionDetail, ProjectionStatus $currentStatus): bool;

    /**
     * Deletes the projection.
     *
     * @return bool True if the operation was successful, false otherwise.
     */
    public function delete(): bool;

    /**
     * Acquires a lock for the projection.
     *
     * @return bool True if the lock was acquired, false otherwise.
     *
     * @throws ProjectionAlreadyRunning If the projection is already running.
     */
    public function acquireLock(): bool;

    /**
     * Updates the lock for the projection if no gap.
     *
     * todo we always return true
     *
     * @param  array $streamPositions The stream positions to update the lock with.
     * @return bool  True if the operation was successful, false otherwise.
     */
    public function attemptUpdateLockAndStreamPositions(array $streamPositions): bool;

    /**
     * Releases the lock for the projection.
     *
     * @return bool True if the operation was successful, false otherwise.
     */
    public function releaseLock(): bool;

    /**
     * Loads the projection status.
     *
     * @return ProjectionStatus The projection status.
     */
    public function loadStatus(): ProjectionStatus;

    /**
     * Loads the projection state and stream positions.
     *
     *
     * @throws ProjectionNotFound If the projection cannot be found in the repository.
     */
    public function loadDetail(): ProjectionDetail;

    /**
     * Checks if the projection exists.
     *
     * @return bool True if the projection exists, false otherwise.
     */
    public function exists(): bool;

    /**
     * Returns the name of the projection.
     *
     * @return string The projection name.
     */
    public function projectionName(): string;
}
