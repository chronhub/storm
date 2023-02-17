<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Closure;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;

interface ProjectorBuilder extends Projector
{
    /**
     * Initialize projection with a callback
     */
    public function initialize(Closure $initCallback): static;

    /**
     * Set stream names to listen to
     */
    public function fromStreams(string ...$streams): static;

    /**
     * Set category names to listen to
     */
    public function fromCategories(string ...$categories): static;

    /**
     * Listen for all streams except those which start with the "$" sign
     */
    public function fromAll(): static;

    /**
     * Set domain event handlers as array
     *
     * eg:
     *      [
     *          function(DomainEvent $event, array $state): array{
     *              ...
     *          },
     *          function(AnotherDomainEvent $event, array $state): array{
     *              ...
     *          }
     *      ]
     *
     * @param  array<string, callable>  $eventHandlers
     */
    public function when(array $eventHandlers): static;

    /**
     * Set domain event handlers as closure
     *
     * eg:      function(DomainEvent $event, array $state): array{
     *              if($event instanceOf of [...])
     *                  [...]
     *          },
     */
    public function whenAny(Closure $eventsHandler): static;

    /**
     * Set the projection query filter
     */
    public function withQueryFilter(QueryFilter $queryFilter): static;
}
