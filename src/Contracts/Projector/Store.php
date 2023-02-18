<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;

interface Store
{
    public function create(): bool;

    /**
     * @throws ProjectionNotFound
     */
    public function loadState(): bool;

    public function stop(): bool;

    public function startAgain(): bool;

    public function loadStatus(): ProjectionStatus;

    public function persist(): bool;

    public function reset(): bool;

    public function delete(bool $withEmittedEvents): bool;

    public function exists(): bool;

    /**
     * @throws ProjectionAlreadyRunning
     */
    public function acquireLock(): bool;

    public function updateLock(): bool;

    public function releaseLock(): bool;

    public function currentStreamName(): string;
}
