<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface WriteLockStrategy
{
    public function acquireLock(string $tableName): bool;

    public function releaseLock(string $tableName): bool;
}
