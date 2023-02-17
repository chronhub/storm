<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Throwable;

interface ReadModel
{
    /**
     * Initialize read model
     */
    public function initialize(): void;

    /**
     * Stack operations on read model
     */
    public function stack(string $operation, mixed ...$arguments): void;

    /**
     * Persist read model operation stacked
     */
    public function persist(): void;

    /**
     * Reset read model projection
     *
     *
     * @throws Throwable
     */
    public function reset(): void;

    /**
     * Delete read model projection
     *
     * @throws Throwable
     */
    public function down(): void;

    /**
     * Check if the read model has already been initialized
     */
    public function isInitialized(): bool;
}
