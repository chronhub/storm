<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Closure;

interface ContextReader
{
    public function initCallback(): ?Closure;

    public function eventHandlers(): callable;

    public function queries(): array;

    public function queryFilter(): QueryFilter;
}
