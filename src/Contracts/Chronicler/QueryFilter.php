<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface QueryFilter
{
    /**
     * Get callable query filter
     */
    public function apply(): callable;
}
