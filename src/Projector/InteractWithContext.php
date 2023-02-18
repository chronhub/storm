<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Closure;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;

trait InteractWithContext
{
    public function initialize(Closure $initCallback): static
    {
        $this->context->initialize($initCallback);

        return $this;
    }

    public function fromStreams(string ...$streams): static
    {
        $this->context->fromStreams(...$streams);

        return $this;
    }

    public function fromCategories(string ...$categories): static
    {
        $this->context->fromCategories(...$categories);

        return $this;
    }

    public function fromAll(): static
    {
        $this->context->fromAll();

        return $this;
    }

    public function when(array $eventHandlers): static
    {
        $this->context->when($eventHandlers);

        return $this;
    }

    public function whenAny(callable $eventsHandler): static
    {
        $this->context->whenAny($eventsHandler);

        return $this;
    }

    public function withQueryFilter(QueryFilter $queryFilter): static
    {
        $this->context->withQueryFilter($queryFilter);

        return $this;
    }
}
