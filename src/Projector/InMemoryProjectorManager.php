<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Throwable;
use Chronhub\Storm\Projector\Exceptions\InMemoryProjectionFailed;

final class InMemoryProjectorManager extends AbstractProjectorManager
{
    /**
     * @throws InMemoryProjectionFailed
     */
    public function stop(string $streamName): void
    {
        $this->updateProjectionStatus($streamName, ProjectionStatus::STOPPING);
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function reset(string $streamName): void
    {
        $this->updateProjectionStatus($streamName, ProjectionStatus::RESETTING);
    }

    /**
     * @throws InMemoryProjectionFailed
     */
    public function delete(string $streamName, bool $withEmittedEvents): void
    {
        $deleteProjectionStatus = $withEmittedEvents
            ? ProjectionStatus::DELETING_WITH_EMITTED_EVENTS
            : ProjectionStatus::DELETING;

        $this->updateProjectionStatus($streamName, $deleteProjectionStatus);
    }

    protected function updateProjectionStatus(string $streamName, ProjectionStatus $projectionStatus): void
    {
        try {
            $success = $this->factory->projectionProvider->updateProjection(
                $streamName,
                ['status' => $projectionStatus->value]
            );
        } catch (Throwable $exception) {
            throw InMemoryProjectionFailed::failedOnUpdateStatus($streamName, $projectionStatus, $exception);
        }

        if (! $success) {
            $this->assertProjectionExists($streamName);
        }
    }
}
