<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Closure;
use DateInterval;

trait InteractWithProjection
{
    public function initialize(Closure $userState): static
    {
        $this->subscriber->subscription->context->initialize($userState);

        return $this;
    }

    public function fromStreams(string ...$streams): static
    {
        $this->subscriber->subscription->context->fromStreams(...$streams);

        return $this;
    }

    public function fromCategories(string ...$categories): static
    {
        $this->subscriber->subscription->context->fromCategories(...$categories);

        return $this;
    }

    public function fromAll(): static
    {
        $this->subscriber->subscription->context->fromAll();

        return $this;
    }

    public function when(Closure $reactors): static
    {
        $this->subscriber->subscription->context->when($reactors);

        return $this;
    }

    public function withQueryFilter(QueryFilter $queryFilter): static
    {
        $this->subscriber->subscription->context->withQueryFilter($queryFilter);

        return $this;
    }

    public function until(DateInterval|string|int $interval): static
    {
        $this->subscriber->subscription->context->until($interval);

        return $this;
    }

    public function withScope(Closure $scope): static
    {
        $this->subscriber->subscription->context->withScope($scope);

        return $this;
    }

    public function getState(): array
    {
        return $this->subscriber->subscription->state->get();
    }
}
