<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectionProvider
{
    /**
     * Create a new projection with the given name and status.
     *
     * @param  string $projectionName The name of the projection to create.
     * @param  string $status         The status of the projection.
     * @return bool   True if the projection was created successfully, false otherwise.
     */
    public function createProjection(string $projectionName, string $status): bool;

    /**
     * Update the data for an existing projection.
     *
     * @param string $projectionName The name of the projection to update.
     * @param array{
     *     "state"?: string,
     *     "position"?: string,
     *     "status"?: string,
     *     "locked_until"?: null|string
     * } $data The data to update.
     * @return bool True if the update was successful, false otherwise.
     */
    public function updateProjection(string $projectionName, array $data): bool;

    /**
     * Delete the data for an existing projection.
     *
     * @param  string $projectionName The name of the projection to delete.
     * @return bool   True if the deletion was successful, false otherwise.
     */
    public function deleteProjection(string $projectionName): bool;

    /**
     * Acquire a lock on a projection with the given name and status.
     *
     * @param  string $projectionName The name of the projection to acquire a lock on.
     * @param  string $status         The status of the projection.
     * @param  string $lockedUntil    The datetime until the lock is valid.
     * @return bool   True if the lock was acquired successfully, false otherwise.
     */
    public function acquireLock(string $projectionName, string $status, string $lockedUntil): bool;

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
     * @return array{string} An array of projection names that match the given names.
     */
    public function filterByNames(string ...$projectionNames): array;

    /**
     * Check if a projection with the given name exists.
     *
     * @param  string $projectionName The name of the projection to check.
     * @return bool   True if the projection exists, false otherwise.
     */
    public function exists(string $projectionName): bool;
}
