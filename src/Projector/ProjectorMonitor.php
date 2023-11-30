<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Projector\ProjectorMonitorInterface;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Throwable;

final readonly class ProjectorMonitor implements ProjectorMonitorInterface
{
    public function __construct(
        private ProjectionProvider $projectionProvider,
        private JsonSerializer $jsonSerializer,
    ) {
    }

    public function markAsStop(string $projectionName): void
    {
        $this->updateProjectionStatus($projectionName, ProjectionStatus::STOPPING);
    }

    public function markAsReset(string $projectionName): void
    {
        $this->updateProjectionStatus($projectionName, ProjectionStatus::RESETTING);
    }

    public function markAsDelete(string $projectionName, bool $withEmittedEvents): void
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
     * @throws ProjectionFailed
     * @throws ProjectionNotFound
     */
    private function updateProjectionStatus(string $projectionName, ProjectionStatus $projectionStatus): void
    {
        try {
            $this->projectionProvider->updateProjection($projectionName, status : $projectionStatus->value);
        } catch (Throwable $exception) {
            if ($exception instanceof ProjectionFailed) {
                throw $exception;
            }

            throw ProjectionFailed::failedOnUpdateStatus($projectionName, $projectionStatus, $exception);
        }
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
}
