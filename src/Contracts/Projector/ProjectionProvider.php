<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectionProvider
{
    /**
     * Create projection
     *
     * @param  string  $name
     * @param  string  $status
     * @return bool
     */
    public function createProjection(string $name, string $status): bool;

    /**
     * Update projection by name
     *
     * @param  string  $name
     * @param  array  $data
     * @return bool
     */
    public function updateProjection(string $name, array $data): bool;

    /**
     * Delete projection by name
     *
     * @param  string  $name
     * @return bool
     */
    public function deleteProjection(string $name): bool;

    /**
     * Acquire projection lock
     *
     * @param  string  $name
     * @param  string  $status
     * @param  string  $lockedUntil
     * @param  string  $datetime
     * @return bool
     */
    public function acquireLock(string $name, string $status, string $lockedUntil, string $datetime): bool;

    /**
     * Retrieve projection by name
     *
     * @param  string  $name
     * @return ProjectionModel|null
     */
    public function retrieve(string $name): ?ProjectionModel;

    /**
     * Return string projection names ordered by ascendant names
     *
     * @return array<string>
     */
    public function filterByNames(string ...$names): array;

    /**
     * Assert projection exists by name
     *
     * @param  string  $name
     * @return bool
     */
    public function projectionExists(string $name): bool;
}
