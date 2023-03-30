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

    public function projectReadModel(string $streamName, ReadModel $readModel, array $options = []): ReadModelProjector
    {
        $context = $this->createContext($options, true);

        $provider = $this->createStore($context, $streamName);

        $repository = $this->createRepository($context, $provider, $readModel);

        return new ProjectReadModel($context, $repository, $this->chronicler, $streamName, $readModel);
    }

    public function statusOf(string $projectionName): string
    {
        $projection = $this->projectionProvider->retrieve($projectionName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $projection->status();
    }

    public function streamPositionsOf(string $projectionName): array
    {
        $projection = $this->projectionProvider->retrieve($projectionName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $this->jsonSerializer->decode($projection->position());
    }

    public function stateOf(string $projectionName): array
    {
        $projection = $this->projectionProvider->retrieve($projectionName);

        if (! $projection) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $this->jsonSerializer->decode($projection->state());
    }

    public function filterNamesByAscendantOrder(string ...$streamNames): array
    {
        return $this->projectionProvider->filterByNames(...$streamNames);
    }

    public function exists(string $projectionName): bool
    {
        return $this->projectionProvider->projectionExists($projectionName);
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
