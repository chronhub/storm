<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;

interface ProjectorManager
{
    /**
     * @param  array<ProjectionOption::*, null|string|int|bool|array>  $options
     */
    public function projectQuery(array $options = []): QueryProjector;

    /**
     * @param  array<ProjectionOption::*, null|string|int|bool|array>  $options
     */
    public function projectProjection(string $streamName, array $options = []): ProjectionProjector;

    /**
     * @param  array<ProjectionOption::*, null|string|int|bool|array>  $options
     */
    public function projectReadModel(string $streamName,
                                     ReadModel $readModel,
                                     array $options = []): ReadModelProjector;

    /**
     * @throws ProjectionNotFound
     */
    public function stop(string $projectionName): void;

    /**
     * @throws ProjectionNotFound
     */
    public function reset(string $projectionName): void;

    /**
     * @throws ProjectionNotFound
     */
    public function delete(string $projectionName, bool $withEmittedEvents): void;

    /**
     * @throws ProjectionNotFound
     */
    public function statusOf(string $projectionName): string;

    /**
     * @return array<string, int<0, max>>
     *
     * @throws ProjectionNotFound
     */
    public function streamPositionsOf(string $projectionName): array;

    /**
     * @return array<string|int, null|string|int|float|bool|array>
     *
     * @throws ProjectionNotFound
     */
    public function stateOf(string $projectionName): array;

    /**
     * @return array<string>
     */
    public function filterNamesByAscendantOrder(string ...$streamNames): array;

    public function exists(string $projectionName): bool;

    public function queryScope(): ProjectionQueryScope;
}
