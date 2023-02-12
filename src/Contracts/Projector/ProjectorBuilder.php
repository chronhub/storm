<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Closure;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;

interface ProjectorBuilder extends Projector
{
    /**
     * Initialize projection with a callback
     *
     * @param  Closure  $initCallback
     * @return static
     */
    public function initialize(Closure $initCallback): static;

    /**
     * Set stream names to listen to
     *
     * @param  string  ...$streams
     * @return static
     */
    public function fromStreams(string ...$streams): static;

    /**
     * Set category names to listen to
     *
     * @param  string  ...$categories
     * @return static
     */
    public function fromCategories(string ...$categories): static;

    /**
     * Listen for all streams except those which start with the "$" sign
     *
     * @return static
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
     * @return static
     */
    public function when(array $eventHandlers): static;

    /**
     * Set domain event handlers as closure
     *
     * eg:      function(DomainEvent $event, array $state): array{
     *              if($event instanceOf of [...])
     *                  [...]
     *          },
     *
     * @param  Closure  $eventsHandler
     * @return static
     */
    public function whenAny(Closure $eventsHandler): static;

    /**
     * Set the projection query filter
     *
     * @param  QueryFilter  $queryFilter
     * @return static
     */
    public function withQueryFilter(QueryFilter $queryFilter): static;
}
