<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Closure;
use DateInterval;

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

    public function whenAny(Closure $eventsHandler): static
    {
        $this->context->whenAny($eventsHandler);

        return $this;
    }

    public function withQueryFilter(QueryFilter $queryFilter): static
    {
        $this->context->withQueryFilter($queryFilter);

        return $this;
    }

    public function withTimer(int|string|DateInterval $interval): static
    {
        $this->context->until($interval);

        return $this;
    }
}
