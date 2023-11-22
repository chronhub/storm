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
     * Store projection data with current projection status.
     *
     * @throws ProjectionFailed When projection data cannot be stored.
     */
    public function persist(ProjectionDetail $projectionDetail, ProjectionStatus $currentStatus): void;

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
     * Updates stream positions and gaps with a new lock.
     * note that should update lock must be called before update
     *
     * @throws ProjectionFailed When projection data cannot be stored.
     * @throws RuntimeException When update lock failed
     */
    public function update(ProjectionDetail $projectionDetail, DateTimeImmutable $currentTime): void;

    /**
     * Should update projection data based on current time.
     */
    public function canUpdate(DateTimeImmutable $currentTime): bool;

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
