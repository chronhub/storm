<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Closure;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;

interface ContextRead
{
    public function initCallback(): ?Closure;

    public function eventHandlers(): callable;

    public function queries(): array;

    public function queryFilter(): QueryFilter;
}
