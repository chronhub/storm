<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyExists;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use LogicException;

interface ProjectionProvider
{
    /**
     * Create a new projection with the given name and status.
     *
     * @param string $projectionName The name of the projection to create.
     * @param string $status         The status of the projection.
     *
     * @throws ProjectionAlreadyExists When a projection with the given name already exists.
     */
    public function createProjection(string $projectionName, string $status): void;

    /**
     * Acquire a lock on a projection with the given name and status.
     *
     * @param string $projectionName The name of the projection to acquire a lock on.
     * @param string $status         The status of the projection.
     * @param string $lockedUntil    The datetime until the lock is valid.
     *
     * @throws ProjectionNotFound       When a projection with the given name doesn't exist.
     * @throws ProjectionAlreadyRunning When a projection with the given name is already running.
     */
    public function acquireLock(string $projectionName, string $status, string $lockedUntil): void;

    /**
     * Update the data for an existing projection.
     *
     * @param string $projectionName The name of the projection to update.
     *
     * @throws ProjectionNotFound When a projection with the given name doesn't exist.
     * @throws LogicException     When the projection has not acquired locked.
     * @throws ProjectionFailed   When the projection data cannot be updated.
     */
    public function updateProjection(
        string $projectionName,
        string $status = null,
        string $state = null,
        string $positions = null,
        bool|string|null $lockedUntil = false
    ): void;

    /**
     * Delete the data for an existing projection.
     *
     * @param string $projectionName The name of the projection to delete.
     *
     * @throws ProjectionNotFound When a projection with the given name doesn't exist.
     * @throws ProjectionFailed   When the projection data cannot be deleted.
     */
    public function deleteProjection(string $projectionName): void;

    /**
     * Retrieve the data for an existing projection.
     *
     * @param  string               $projectionName The name of the projection to retrieve.
     * @return ProjectionModel|null The projection data, or null if the projection doesn't exist.
     */
    public function retrieve(string $projectionName): ?ProjectionModel;

    /**
     * Filter projections by their names.
     *
     * @param  string        ...$projectionNames The names of the projections to filter.
     * @return array<string> An array of string projection names that match the given names.
     */
    public function filterByNames(string ...$projectionNames): array;

    /**
     * Check if a projection with the given name exists.
     *
     * @param string $projectionName The name of the projection to check.
     */
    public function exists(string $projectionName): bool;
}
