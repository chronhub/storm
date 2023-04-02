<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Closure;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;

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
