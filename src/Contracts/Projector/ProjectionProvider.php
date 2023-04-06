<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectionProvider
{
    public function createProjection(string $projectionName, string $status): bool;

    /**
     * @param array{"state"?: string, "position"?: string, "status"?: string, "locked_until"?: null|string} $data
     */
    public function updateProjection(string $projectionName, array $data): bool;

    public function deleteProjection(string $projectionName): bool;

    public function acquireLock(string $projectionName, string $status, string $lockedUntil, string $datetime): bool;

    public function retrieve(string $projectionName): ?ProjectionModel;

    /**
     * @return array{string}
     */
    public function filterByNames(string ...$projectionNames): array;

    public function exists(string $projectionName): bool;
}
