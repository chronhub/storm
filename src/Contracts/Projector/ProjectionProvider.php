<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyExists;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;

interface ProjectionProvider
{
    /**
     * Create a new projection with the given name and status.
     *
     * @throws ProjectionAlreadyExists When a projection with the given name already exists.
     */
    public function createProjection(string $projectionName, string $status): void;

    /**
     * Acquire a lock on a projection with the given name and status.
     *
     * @throws ProjectionNotFound       When a projection with the given name doesn't exist.
     * @throws ProjectionAlreadyRunning When a projection with the given name is already running.
     */
    public function acquireLock(string $projectionName, string $status, string $lockedUntil): void;

    /**
     * Update the data for an existing projection.
     *
     * @throws ProjectionNotFound When the projection with the given name doesn't exist.
     * @throws ProjectionFailed   When the projection has not acquired locked.
     * @throws ProjectionFailed   When the projection data cannot be updated.
     */
    public function updateProjection(
        string $projectionName,
        string $status = null,
        string $state = null,
        string $position = null,
        bool|string|null $lockedUntil = false
    ): void;

    /**
     * Delete an existing projection.
     *
     * @throws ProjectionNotFound When a projection with the given name doesn't exist.
     * @throws ProjectionFailed   When the projection failed to be deleted.
     */
    public function deleteProjection(string $projectionName): void;

    /**
     * Retrieve model for an existing projection.
     */
    public function retrieve(string $projectionName): ?ProjectionModel;

    /**
     * Filter projections by their names.
     *
     * @return array<string> An array of string projection names that match the given names.
     */
    public function filterByNames(string ...$projectionNames): array;

    /**
     * Check if a projection with the given name exists.
     */
    public function exists(string $projectionName): bool;
}
