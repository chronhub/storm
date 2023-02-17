<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Throwable;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Contracts\Projector\Store;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\Repository\InMemoryStore;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\Exceptions\InMemoryProjectionFailed;
use Chronhub\Storm\Projector\Repository\ReadModelProjectorRepository;
use Chronhub\Storm\Projector\Repository\PersistentProjectorRepository;

final class InMemoryProjectorManager extends AbstractProjectorManager
{
    protected function createProjectorRepository(Context $context, Store $store, ?ReadModel $readModel): ProjectorRepository
    {
        $store = new InMemoryStore($store);

        if ($readModel) {
            return new ReadModelProjectorRepository($context, $store, $readModel);
        }

        return new PersistentProjectorRepository($context, $store, $this->chronicler);
    }

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

    /**
     * @throws ProjectionNotFound
     * @throws InMemoryProjectionFailed
     */
    protected function updateProjectionStatus(string $streamName, ProjectionStatus $projectionStatus): void
    {
        try {
            $success = $this->projectionProvider->updateProjection(
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
