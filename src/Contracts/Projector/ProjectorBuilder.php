<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Closure;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;

interface ProjectorBuilder extends Projector
{
    public function initialize(Closure $initCallback): static;

    public function fromStreams(string ...$streams): static;

    public function fromCategories(string ...$categories): static;

    public function fromAll(): static;

    /**
     * @param  array{string, callable}  $eventsHandlers
     */
    public function when(array $eventsHandlers): static;

    /**
     * fixMe param
     */
    public function whenAny(callable $eventsHandlers): static;

    public function withQueryFilter(QueryFilter $queryFilter): static;
}
