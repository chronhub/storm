<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyExists;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\ProjectionDetail;
use DateTimeImmutable;

interface ProjectionRepositoryInterface
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
    public function start(): void;

    /**
     * Persists projection data
     *
     * when current time is given we check if we can update projection data
     * with a new refresh lock, otherwise we just acquire a new lock
     *
     * @throws ProjectionFailed When projection data cannot be stored.
     * @throws RuntimeException When update lock failed if current time is given
     */
    public function persist(ProjectionDetail $projectionDetail, ProjectionStatus $currentStatus): void;

    /**
     * Persists projection data when lock threshold is reached
     *
     * @throws ProjectionFailed When projection data cannot be stored.
     * @throws RuntimeException When refresh lock failed
     */
    public function persistWhenLockThresholdIsReached(ProjectionDetail $projectionDetail, DateTimeImmutable $currentTime): void;

    /**
     * Stops the projection and store data.
     *
     * @throws ProjectionNotFound When a projection with the given name doesn't exist.
     * @throws ProjectionFailed   When projection data cannot be stored.
     */
    public function stop(ProjectionDetail $projectionDetail): void;

    /**
     * Starts the projection again.
     *
     * @throws ProjectionNotFound When a projection with the given name doesn't exist.
     * @throws ProjectionFailed   When projection failed to update data.
     */
    public function startAgain(): void;

    /**
     * Resets projection data.
     */
    public function reset(ProjectionDetail $projectionDetail, ProjectionStatus $currentStatus): void;

    /**
     * Deletes the projection.
     *
     * @param bool $withEmittedEvents only use as flag to dispatch event
     *
     * @throws ProjectionNotFound When a projection with the given name doesn't exist.
     * @throws ProjectionFailed   When projection data cannot be deleted.
     */
    public function delete(bool $withEmittedEvents): void;

    /**
     * Should update projection data based on current time.
     */
    public function canRefreshLock(DateTimeImmutable $currentTime): bool;

    /**
     * Releases the lock for the projection.
     */
    public function release(): void;

    /**
     * Loads the projection status.
     *
     * @return ProjectionStatus The projection status.
     */
    public function loadStatus(): ProjectionStatus;

    /**
     * Loads the projection state and stream positions.
     *
     * @throws ProjectionNotFound If the projection cannot be found in the repository.
     */
    public function loadDetail(): ProjectionDetail;

    /**
     * Checks if the projection exists.
     */
    public function exists(): bool;

    /**
     * Returns the name of the projection.
     *
     * @return non-empty-string The projection name.
     */
    public function projectionName(): string;
}
