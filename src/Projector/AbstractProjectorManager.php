<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectorManager;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ProjectionProjector;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use function json_decode;

abstract class AbstractProjectorManager implements ProjectorManager
{
    //wip
    public const JSON_FLAGS = JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY | JSON_BIGINT_AS_STRING;

    public function __construct(protected readonly ProjectorManagerFactory $factory)
    {
    }

    public function projectQuery(array $options = []): QueryProjector
    {
        $context = $this->factory->createContext($options, null);

        return new ProjectQuery($context, $this->factory->chronicler);
    }

    public function projectProjection(string $streamName, array $options = []): ProjectionProjector
    {
        $context = $this->factory->createContext($options, new EventCounter());

        $provider = $this->factory->createStore($context, $streamName);

        $repository = $this->factory->makeRepository($context, $provider, null);

        return new ProjectProjection($context, $repository, $this->factory->chronicler, $streamName);
    }

    public function projectReadModel(string $streamName, ReadModel $readModel, array $option = []): ReadModelProjector
    {
        $context = $this->factory->createContext($option, new EventCounter());

        $provider = $this->factory->createStore($context, $streamName);

        $repository = $this->factory->makeRepository($context, $provider, $readModel);

        return new ProjectReadModel($context, $repository, $this->factory->chronicler, $streamName, $readModel);
    }

    public function statusOf(string $name): string
    {
        $projection = $this->factory->projectionProvider->retrieve($name);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($name);
        }

        return $projection->status();
    }

    public function streamPositionsOf(string $name): array
    {
        $projection = $this->factory->projectionProvider->retrieve($name);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($name);
        }

        return json_decode($projection->position(), true, 512, self::JSON_FLAGS);
    }

    public function stateOf(string $name): array
    {
        $projection = $this->factory->projectionProvider->retrieve($name);

        if (! $projection) {
            throw ProjectionNotFound::withName($name);
        }

        return json_decode($projection->state(), true, 512, self::JSON_FLAGS);
    }

    public function filterNamesOf(string ...$names): array
    {
        return $this->factory->projectionProvider->filterByNames(...$names);
    }

    public function exists(string $name): bool
    {
        return $this->factory->projectionProvider->projectionExists($name);
    }

    public function queryScope(): ProjectionQueryScope
    {
        return $this->factory->queryScope;
    }

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
