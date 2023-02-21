<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Closure;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;

/**
 * @template T of Closure(DomainEvent): void|Closure(DomainEvent, array): array
 */
interface ProjectorBuilder extends Projector
{
    public function initialize(Closure $initCallback): static;

    public function fromStreams(string ...$streams): static;

    public function fromCategories(string ...$categories): static;

    public function fromAll(): static;

    /**
     * @param  array{T}  $eventsHandlers
     */
    public function when(array $eventsHandlers): static;

    /**
     * @phpstan-param  T  $eventsHandlers
     */
    public function whenAny(Closure $eventsHandlers): static;

    public function withQueryFilter(QueryFilter $queryFilter): static;
}
