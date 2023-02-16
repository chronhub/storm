<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use JsonSerializable;

interface PersistentState extends JsonSerializable
{
    /**
     * Set the projection state
     */
    public function put(array $state): void;

    /**
     * Get the current projection state
     */
    public function get(): array;

    /**
     * Reset the projection state
     */
    public function reset(): void;
}
