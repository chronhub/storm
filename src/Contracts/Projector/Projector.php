<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface Projector
{
    /**
     * Run projection
     *
     * @return void
     */
    public function run(bool $inBackground): void;

    /**
     * Stop projection
     *
     * @return void
     */
    public function stop(): void;

    /**
     * Reset projection
     *
     * @return void
     */
    public function reset(): void;

    /**
     * Return projection state
     *
     * @return array<int|string, int|float|string|bool|array>
     */
    public function getState(): array;
}
