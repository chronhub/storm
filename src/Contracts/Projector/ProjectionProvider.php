<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectionProvider
{
    /**
     * Create projection
     */
    public function createProjection(string $name, string $status): bool;

    /**
     * Update projection by name
     */
    public function updateProjection(string $name, array $data): bool;

    /**
     * Delete projection by name
     */
    public function deleteProjection(string $name): bool;

    /**
     * Acquire projection lock
     */
    public function acquireLock(string $name, string $status, string $lockedUntil, string $datetime): bool;

    /**
     * Retrieve projection by name
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
     */
    public function projectionExists(string $name): bool;
}
