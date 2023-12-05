<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Scheme\RunProjection;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Closure;
use DateInterval;

trait InteractWithProjection
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

    public function run(bool $inBackground): void
    {
        $this->subscription->start($inBackground);

        $project = new RunProjection($this->subscription, $this->newWorkflow());

        $project->beginCycle();
    }

    public function getState(): array
    {
        return $this->subscription->state()->get();
    }

    /**
     * @internal
     */
    public function subscription(): Subscription
    {
        return $this->subscription;
    }

    protected function context(): ContextReaderInterface
    {
        return $this->subscription->context();
    }

    /**
     * @internal
     */
    abstract public function getScope(): ProjectorScope;

    abstract protected function newWorkflow(): Workflow;
}
