<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface InMemoryQueryFilter extends QueryFilter
{
    public function orderBy(): string;
}
