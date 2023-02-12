<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use JsonSerializable;

interface PersistentState extends JsonSerializable
{
    /**
     * Set the projection state
     *
     * @param  array  $state
     * @return void
     */
    public function put(array $state): void;

    /**
     * Get the current projection state
     *
     * @return array
     */
    public function get(): array;

    /**
     * Reset the projection state
     *
     * @return void
     */
    public function reset(): void;
}
