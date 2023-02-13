<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface WriteLockStrategy
{
    /**
     * @param  string  $tableName
     * @return bool
     */
    public function acquireLock(string $tableName): bool;

    /**
     * @param  string  $tableName
     * @return bool
     */
    public function releaseLock(string $tableName): bool;
}
