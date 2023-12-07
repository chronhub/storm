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
        $this->subscription->context()->initialize($userState);

        return $this;
    }

    public function fromStreams(string ...$streams): static
    {
        $this->subscription->context()->fromStreams(...$streams);

        return $this;
    }

    public function fromCategories(string ...$categories): static
    {
        $this->subscription->context()->fromCategories(...$categories);

        return $this;
    }

    public function fromAll(): static
    {
        $this->subscription->context()->fromAll();

        return $this;
    }

    public function when(Closure $reactors): static
    {
        $this->subscription->context()->when($reactors);

        return $this;
    }

    public function withQueryFilter(QueryFilter $queryFilter): static
    {
        $this->subscription->context()->withQueryFilter($queryFilter);

        return $this;
    }

    public function until(DateInterval|string|int $interval): static
    {
        $this->subscription->context()->until($interval);

        return $this;
    }

    public function withScope(Closure $scope): static
    {
        $this->subscription->context()->withScope($scope);

        return $this;
    }

    public function getState(): array
    {
        return $this->subscription->state()->get();
    }
}
