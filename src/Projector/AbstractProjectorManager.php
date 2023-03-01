<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Contracts\Projector\Store;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectorManager;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ProjectionProjector;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;

abstract class AbstractProjectorManager implements ProjectorManager
{
    use HasConstructableProjectorManager;

    public function projectQuery(array $options = []): QueryProjector
    {
        $context = $this->createContext($options, false);

        return new ProjectQuery($context, $this->chronicler);
    }

    public function projectProjection(string $streamName, array $options = []): ProjectionProjector
    {
        $context = $this->createContext($options, true);

        $provider = $this->createStore($context, $streamName);

        $repository = $this->createRepository($context, $provider, null);

        return new ProjectProjection($context, $repository, $this->chronicler, $streamName);
    }

    public function projectReadModel(string $streamName, ReadModel $readModel, array $option = []): ReadModelProjector
    {
        $context = $this->createContext($option, true);

        $provider = $this->createStore($context, $streamName);

        $repository = $this->createRepository($context, $provider, $readModel);

        return new ProjectReadModel($context, $repository, $this->chronicler, $streamName, $readModel);
    }

    public function statusOf(string $name): string
    {
        $projection = $this->projectionProvider->retrieve($name);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($name);
        }

        return $projection->status();
    }

    public function streamPositionsOf(string $name): array
    {
        $projection = $this->projectionProvider->retrieve($name);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($name);
        }

        return $this->jsonSerializer->decode($projection->position());
    }

    public function stateOf(string $name): array
    {
        $projection = $this->projectionProvider->retrieve($name);

        if (! $projection) {
            throw ProjectionNotFound::withName($name);
        }

        return $this->jsonSerializer->decode($projection->state());
    }

    public function filterNamesByAscendantOrder(string ...$names): array
    {
        return $this->projectionProvider->filterByNames(...$names);
    }

    public function exists(string $name): bool
    {
        return $this->projectionProvider->projectionExists($name);
    }

    public function queryScope(): ProjectionQueryScope
    {
        return $this->queryScope;
    }

    abstract protected function createRepository(Context $context,
                                                 Store $store,
                                                 ?ReadModel $readModel): ProjectorRepository;

    /**
     * @throws ProjectionNotFound
     */
    protected function assertProjectionExists(string $projectionName): void
    {
        if (! $this->exists($projectionName)) {
            throw ProjectionNotFound::withName($projectionName);
        }
    }
}
