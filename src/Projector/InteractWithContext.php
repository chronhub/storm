<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Closure;
use DateInterval;

trait InteractWithContext
{
    public function initialize(Closure $userState): static
    {
        $this->context()->initialize($userState);

        return $this;
    }

    public function fromStreams(string ...$streams): static
    {
        $this->context()->fromStreams(...$streams);

        return $this;
    }

    public function fromCategories(string ...$categories): static
    {
        $this->context()->fromCategories(...$categories);

        return $this;
    }

    public function fromAll(): static
    {
        $this->context()->fromAll();

        return $this;
    }

    public function when(Closure $reactors): static
    {
        $this->context()->when($reactors);

        return $this;
    }

    public function withQueryFilter(QueryFilter $queryFilter): static
    {
        $this->context()->withQueryFilter($queryFilter);

        return $this;
    }

    public function until(DateInterval|string|int $interval): static
    {
        $this->context()->until($interval);

        return $this;
    }

    public function withScope(Closure $scope): static
    {
        $this->context()->withScope($scope);

        return $this;
    }

    protected function context(): ContextReaderInterface
    {
        return $this->subscription->context();
    }
}
