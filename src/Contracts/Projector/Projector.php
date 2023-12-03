<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface Projector
{
    /**
     * Run in the background.
     */
    public function run(bool $inBackground): void;

    /**
     * Stop the projection.
     */
    public function stop(): void;

    /**
     * Reset the projection.
     */
    public function reset(): void;

    /**
     * Get the projection state.
     */
    public function getState(): array;
}
