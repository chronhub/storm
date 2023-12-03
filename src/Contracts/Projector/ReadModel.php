<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Throwable;

interface ReadModel
{
    /**
     * Initializes the read model.
     */
    public function initialize(): void;

    /**
     * Stacks an operation to be applied to the read model.
     *
     * @param string $operation    The operation to apply.
     * @param mixed  ...$arguments The arguments for the operation.
     */
    public function stack(string $operation, mixed ...$arguments): void;

    /**
     * Persist any changes made to the read model made
     * by the stacked operations.
     */
    public function persist(): void;

    /**
     * Resets the read model to its initial state.
     *
     * @throws Throwable
     */
    public function reset(): void;

    /**
     * Deletes the read model.
     *
     * @throws Throwable
     */
    public function down(): void;

    /**
     * Check if the read model has been initialized.
     */
    public function isInitialized(): bool;
}
