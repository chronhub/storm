<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Throwable;

final readonly class ProjectorProvider
{
    public function __construct(
        private ProjectionProvider $projectionProvider,
        private JsonSerializer $jsonSerializer,
    ) {
    }

    /**
     * @throws ProjectionFailed
     * @throws ProjectionNotFound
     */
    public function updateProjectionStatus(string $projectionName, ProjectionStatus $projectionStatus): void
    {
        try {
            $success = $this->projectionProvider->updateProjection(
                $projectionName, status : $projectionStatus->value
            );
        } catch (Throwable $exception) {
            throw ProjectionFailed::failedOnUpdateStatus($projectionName, $projectionStatus, $exception);
        }

        if (! $success) {
            $this->assertProjectionExists($projectionName);
        }
    }

    public function stop(string $projectionName): void
    {
        $this->updateProjectionStatus($projectionName, ProjectionStatus::STOPPING);
    }

    public function reset(string $projectionName): void
    {
        $this->updateProjectionStatus($projectionName, ProjectionStatus::RESETTING);
    }

    public function delete(string $projectionName, bool $withEmittedEvents): void
    {
        $deleteProjectionStatus = $withEmittedEvents
            ? ProjectionStatus::DELETING_WITH_EMITTED_EVENTS
            : ProjectionStatus::DELETING;

        $this->updateProjectionStatus($projectionName, $deleteProjectionStatus);
    }

    public function statusOf(string $projectionName): string
    {
        return $this->tryRetrieveProjectionByName($projectionName)->status();
    }

    public function streamPositionsOf(string $projectionName): array
    {
        $projection = $this->tryRetrieveProjectionByName($projectionName);

        return $this->jsonSerializer->decode($projection->positions());
    }

    public function stateOf(string $projectionName): array
    {
        $projection = $this->tryRetrieveProjectionByName($projectionName);

        return $this->jsonSerializer->decode($projection->state());
    }

    public function filterNamesByAscendantOrder(string ...$streamNames): array
    {
        return $this->projectionProvider->filterByNames(...$streamNames);
    }

    public function exists(string $projectionName): bool
    {
        return $this->projectionProvider->exists($projectionName);
    }

    /**
     * @throws ProjectionNotFound
     */
    private function tryRetrieveProjectionByName(string $projectionName): ProjectionModel
    {
        $projection = $this->projectionProvider->retrieve($projectionName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $projection;
    }

    /**
     * @throws ProjectionNotFound
     */
    private function assertProjectionExists(string $projectionName): void
    {
        if (! $this->exists($projectionName)) {
            throw ProjectionNotFound::withName($projectionName);
        }
    }
}
