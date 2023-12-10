<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectionStateInterface
{
    /**
     * Set projection state.
     */
    public function put(array $state): void;

    /**
     * Get projection state.
     */
    public function get(): array;

    /**
     * Reset projection state.
     */
    public function reset(): void;
}
