<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectionProvider
{
    public function createProjection(string $name, string $status): bool;

    /**
     * @param  array{"state"?: string, "position"?: string, "status"?: string, "locked_until"?: null|string}  $data
     */
    public function updateProjection(string $name, array $data): bool;

    public function deleteProjection(string $name): bool;

    public function acquireLock(string $name, string $status, string $lockedUntil, string $datetime): bool;

    public function retrieve(string $name): ?ProjectionModel;

    /**
     * @return array{string}
     */
    public function filterByNames(string ...$projectionNames): array;

    public function projectionExists(string $name): bool;
}
