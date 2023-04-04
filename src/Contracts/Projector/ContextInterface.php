<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Closure;

interface ContextInterface extends ContextReader
{
    public function initialize(Closure $initCallback): self;

    public function withQueryFilter(QueryFilter $queryFilter): self;

    public function fromStreams(string ...$streamNames): self;

    public function fromCategories(string ...$categories): self;

    public function fromAll(): self;

    public function when(array $eventHandlers): self;

    public function whenAny(callable $eventHandler): self;
}
