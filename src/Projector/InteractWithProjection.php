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
        $this->context->initialize($userState);

        return $this;
    }

    public function subscribeToStream(string ...$streams): static
    {
        $this->context->subscribeToStream(...$streams);

        return $this;
    }

    public function subscribeToCategory(string ...$categories): static
    {
        $this->context->subscribeToCategory(...$categories);

        return $this;
    }

    public function subscribeToAll(): static
    {
        $this->context->subscribeToAll();

        return $this;
    }

    public function when(Closure $reactors): static
    {
        $this->context->when($reactors);

        return $this;
    }

    public function withQueryFilter(QueryFilter $queryFilter): static
    {
        $this->context->withQueryFilter($queryFilter);

        return $this;
    }

    public function until(DateInterval|string|int $interval): static
    {
        $this->context->until($interval);

        return $this;
    }

    public function withScope(Closure $scope): static
    {
        $this->context->withScope($scope);

        return $this;
    }

    public function withKeepState(): static
    {
        $this->context->withKeepState();

        return $this;
    }

    public function getState(): array
    {
        return $this->subscriber->getState();
    }
}
