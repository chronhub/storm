<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Throwable;

interface ReadModel
{
    public function initialize(): void;

    public function stack(string $operation, mixed ...$arguments): void;

    public function persist(): void;

    /**
     * @throws Throwable
     */
    public function reset(): void;

    /**
     * @throws Throwable
     */
    public function down(): void;

    public function isInitialized(): bool;
}
