<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectorManagerInterface
{
    /**
     * Create a new query projector.
     *
     * @param array<ProjectionOption::*, null|string|int|bool|array> $options
     */
    public function newQueryProjector(array $options = []): QueryProjector;

    /**
     * Create a new emitter projector.
     *
     * @param array<ProjectionOption::*, null|string|int|bool|array> $options
     */
    public function newEmitterProjector(string $streamName, array $options = []): EmitterProjector;

    /**
     * Create a new read model projector.
     *
     * @param array<ProjectionOption::*, null|string|int|bool|array> $options
     */
    public function newReadModelProjector(
        string $streamName,
        ReadModel $readModel,
        array $options = []): ReadModelProjector;

    /**
     * Get the projection query scope.
     */
    public function queryScope(): ?ProjectionQueryScope;

    /**
     * Get the projector monitor.
     */
    public function monitor(): ProjectorSupervisorInterface;
}
