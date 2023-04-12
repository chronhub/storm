<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\ProjectionStatus;

interface ProjectionRepositoryInterface
{
    public function create(ProjectionStatus $status): bool;

    /**
     * @throws ProjectionNotFound
     */
    public function loadState(): array;

    public function stop(array $streamPositions, array $state): bool;

    public function startAgain(): bool;

    public function loadStatus(): ProjectionStatus;

    public function persist(array $streamPositions, array $state): bool;

    public function reset(array $streamPositions, array $state, ProjectionStatus $currentStatus): bool;

    public function delete(): bool;

    /**
     * @throws ProjectionAlreadyRunning
     */
    public function acquireLock(): bool;

    public function updateLock(array $streamPositions): bool;

    public function releaseLock(): bool;

    public function exists(): bool;

    public function projectionName(): string;
}
