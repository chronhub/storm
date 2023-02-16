<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;

interface ProjectorManager
{
    /**
     * Create a new query projection instance
     *
     * @param  array<string, int|bool>  $options
     */
    public function projectQuery(array $options = []): QueryProjector;

    /**
     * Create a new persistent projection instance
     *
     * @param  string  $streamName
     * @param  array<string, int|bool>  $options
     */
    public function projectProjection(string $streamName, array $options = []): ProjectionProjector;

    /**
     * Create a new read model projection instance
     *
     * @param  string  $streamName
     * @param  ReadModel  $readModel
     * @param  array<string, int|bool>  $option
     */
    public function projectReadModel(string $streamName,
                                     ReadModel $readModel,
                                     array $option = []): ReadModelProjector;

    /**
     * Stop projection by stream name
     *
     * @param  string  $streamName
     *
     * @throws ProjectionNotFound
     */
    public function stop(string $streamName): void;

    /**
     * Stop projection by stream name
     *
     * @param  string  $streamName
     *
     * @throws ProjectionNotFound
     */
    public function reset(string $streamName): void;

    /**
     * Delete projection by name and with or without his emitted events
     *
     * @param  string  $streamName
     * @param  bool  $withEmittedEvents
     *
     * @throws ProjectionNotFound
     */
    public function delete(string $streamName, bool $withEmittedEvents): void;

    /**
     * Fetch status of projection name
     *
     * @throws ProjectionNotFound
     */
    public function statusOf(string $name): string;

    /**
     * Fetch stream positions of projection name
     *
     * @return array<string, int>
     *
     * @throws ProjectionNotFound
     */
    public function streamPositionsOf(string $name): array;

    /**
     * Fetch state of projection name
     *
     * @return array<string|int, null|string|int|float|bool|array>
     *
     * @throws ProjectionNotFound
     */
    public function stateOf(string $name): array;

    /**
     * Filter projection by stream name(s)
     * it return streams order by ascendant name
     *
     * @param  string  ...$names
     * @return array<string>
     */
    public function filterNamesOf(string ...$names): array;

    /**
     * Check if stream exists
     *
     * @param  string  $name
     * @return bool
     */
    public function exists(string $name): bool;

    /**
     * Get the current query scope
     *
     * @return ProjectionQueryScope
     */
    public function queryScope(): ProjectionQueryScope;
}
