<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Throwable;

interface ReadModel extends StackedReadModel
{
    /**
     * Initializes the read model.
     */
    public function initialize(): void;

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
