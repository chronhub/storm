<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface Projector
{
    /**
     * Run in the background or once.
     */
    public function run(bool $inBackground): void;

    /**
     * Get the projection state.
     */
    public function getState(): array;
}
