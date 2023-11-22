<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;

interface ProjectorMonitorInterface
{
    /**
     * Stop the projection.
     *
     * @throws ProjectionNotFound
     */
    public function stop(string $projectionName): void;

    /**
     * Reset the projection.
     *
     * @throws ProjectionNotFound
     */
    public function reset(string $projectionName): void;

    /**
     * Delete the projection and optionally delete emitted events.
     *
     * @throws ProjectionNotFound
     */
    public function delete(string $projectionName, bool $withEmittedEvents): void;

    /**
     * Get the projection status.
     *
     * @throws ProjectionNotFound
     */
    public function statusOf(string $projectionName): string;

    /**
     * Get the projection stream positions.
     *
     * @return array<string,int<0,max>>
     *
     * @throws ProjectionNotFound
     */
    public function streamPositionsOf(string $projectionName): array;

    /**
     * Get the projection state.
     *
     * @return array<string|int,null|string|int|float|bool|array>
     *
     * @throws ProjectionNotFound
     */
    public function stateOf(string $projectionName): array;

    /**
     * Filter projection names by ascendant order.
     *
     * @return array<string>
     */
    public function filterNamesByAscendantOrder(string ...$streamNames): array;

    /**
     * Check if projection exists.
     */
    public function exists(string $projectionName): bool;
}
