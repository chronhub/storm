<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Throwable;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Contracts\Projector\ProjectorManagerInterface;

final readonly class ProjectorManager implements ProjectorManagerInterface
{
    public function __construct(private AbstractSubscriptionFactory $subscriptionFactory)
    {
    }

    public function query(array $options = []): QueryProjector
    {
        return new ProjectQuery(
            $this->subscriptionFactory->createQuerySubscription($options),
            $this->subscriptionFactory->createContextBuilder(),
            $this->subscriptionFactory->chronicler
        );
    }

    public function emitter(string $streamName, array $options = []): EmitterProjector
    {
        $subscription = $this->subscriptionFactory->createEmitterSubscription($options);

        return new ProjectEmitter(
            $subscription,
            $this->subscriptionFactory->createContextBuilder(),
            $this->subscriptionFactory->createSubscriptionManagement($subscription, $streamName, null),
            $this->subscriptionFactory->chronicler,
            $streamName
        );
    }

    public function readModel(string $streamName, ReadModel $readModel, array $options = []): ReadModelProjector
    {
        $subscription = $this->subscriptionFactory->createReadModelSubscription($options);

        return new ProjectReadModel(
            $subscription,
            $this->subscriptionFactory->createContextBuilder(),
            $this->subscriptionFactory->createSubscriptionManagement($subscription, $streamName, $readModel),
            $this->subscriptionFactory->chronicler,
            $streamName,
            $readModel
        );
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

        return $this->subscriptionFactory->jsonSerializer->decode($projection->position());
    }

    public function stateOf(string $projectionName): array
    {
        $projection = $this->tryRetrieveProjectionByName($projectionName);

        return $this->subscriptionFactory->jsonSerializer->decode($projection->state());
    }

    public function filterNamesByAscendantOrder(string ...$streamNames): array
    {
        return $this->subscriptionFactory->projectionProvider->filterByNames(...$streamNames);
    }

    public function exists(string $projectionName): bool
    {
        return $this->subscriptionFactory->projectionProvider->exists($projectionName);
    }

    public function queryScope(): ProjectionQueryScope
    {
        return $this->subscriptionFactory->queryScope;
    }

    private function tryRetrieveProjectionByName(string $projectionName): ProjectionModel
    {
        $projection = $this->subscriptionFactory->projectionProvider->retrieve($projectionName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $projection;
    }

    /**
     * @throws ProjectionNotFound
     * @throws ProjectionFailed
     */
    private function updateProjectionStatus(string $projectionName, ProjectionStatus $projectionStatus): void
    {
        try {
            $success = $this->subscriptionFactory->projectionProvider->updateProjection(
                $projectionName,
                ['status' => $projectionStatus->value]
            );
        } catch (Throwable $exception) {
            throw ProjectionFailed::failedOnUpdateStatus($projectionName, $projectionStatus, $exception);
        }

        if (! $success) {
            $this->assertProjectionExists($projectionName);
        }
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
