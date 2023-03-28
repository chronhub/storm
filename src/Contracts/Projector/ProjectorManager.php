<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;

interface ProjectorManager
{
    /**
     * @param  array<SubscriptionOption::*, null|string|int|bool|array>  $options
     */
    public function projectQuery(array $options = []): QueryProjector;

    /**
     * @param  array<SubscriptionOption::*, null|string|int|bool|array>  $options
     */
    public function projectProjection(string $streamName, array $options = []): ProjectionProjector;

    /**
     * @param  array<SubscriptionOption::*, null|string|int|bool|array>  $option
     */
    public function projectReadModel(string $streamName,
                                     ReadModel $readModel,
                                     array $option = []): ReadModelProjector;

    /**
     * @throws ProjectionNotFound
     */
    public function stop(string $streamName): void;

    /**
     * @throws ProjectionNotFound
     */
    public function reset(string $streamName): void;

    /**
     * @throws ProjectionNotFound
     */
    public function delete(string $streamName, bool $withEmittedEvents): void;

    /**
     * @throws ProjectionNotFound
     */
    public function statusOf(string $name): string;

    /**
     * @return array<string, int<0, max>>
     *
     * @throws ProjectionNotFound
     */
    public function streamPositionsOf(string $name): array;

    /**
     * @return array<string|int, null|string|int|float|bool|array>
     *
     * @throws ProjectionNotFound
     */
    public function stateOf(string $name): array;

    /**
     * @return array<string>
     */
    public function filterNamesByAscendantOrder(string ...$names): array;

    public function exists(string $name): bool;

    public function queryScope(): ProjectionQueryScope;
}
