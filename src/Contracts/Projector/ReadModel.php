<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Throwable;

interface ReadModel
{
    /**
     * Initialize read model
     *
     * @return void
     */
    public function initialize(): void;

    /**
     * Stack operations on read model
     *
     * @param  string  $operation
     * @param  mixed  ...$arguments
     * @return void
     */
    public function stack(string $operation, mixed ...$arguments): void;

    /**
     * Persist read model operation stacked
     *
     * @return void
     */
    public function persist(): void;

    /**
     * Reset read model projection
     *
     * @return void
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
     *
     * @return bool
     */
    public function isInitialized(): bool;
}
