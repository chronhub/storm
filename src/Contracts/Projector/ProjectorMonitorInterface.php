<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;

interface ProjectorMonitorInterface
{
    /**
     * Stop the projection.
     *
     * @throws ProjectionNotFound when projection is not found
     * @throws ProjectionFailed   when update projection status failed
     */
    public function markAsStop(string $projectionName): void;

    /**
     * Reset the projection.
     *
     * @throws ProjectionNotFound when projection is not found
     * @throws ProjectionFailed   when update projection status failed
     */
    public function markAsReset(string $projectionName): void;

    /**
     * Delete the projection and optionally delete emitted events.
     *
     * @throws ProjectionNotFound when projection is not found
     * @throws ProjectionFailed   when update projection status failed
     */
    public function markAsDelete(string $projectionName, bool $withEmittedEvents): void;

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
     * @return array<string|int,mixed>
     *
     * @throws ProjectionNotFound
     */
    public function stateOf(string $projectionName): array;

    /**
     * Filter projection names which exist.
     *
     * @return array<string>
     */
    public function filterNames(string ...$streamNames): array;

    /**
     * Check if the projection exists.
     */
    public function exists(string $projectionName): bool;
}
