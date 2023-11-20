<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Contracts\Projector\ProjectorManagerInterface;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\Subscription\AbstractSubscriptionFactory;
use Throwable;

final readonly class ProjectorManager implements ProjectorManagerInterface
{
    public function __construct(private AbstractSubscriptionFactory $subscriptionFactory)
    {
    }

    public function newQuery(array $options = []): QueryProjector
    {
        return new ProjectQuery(
            $this->subscriptionFactory->createQuerySubscription($options),
            $this->subscriptionFactory->createContextBuilder(),
        );
    }

    public function newEmitter(string $streamName, array $options = []): EmitterProjector
    {
        return new ProjectEmitter(
            $this->subscriptionFactory->createEmitterSubscription($streamName, $options),
            $this->subscriptionFactory->createContextBuilder(),
            $streamName
        );
    }

    public function newReadModel(string $streamName, ReadModel $readModel, array $options = []): ReadModelProjector
    {
        return new ProjectReadModel(
            $this->subscriptionFactory->createReadModelSubscription($streamName, $readModel, $options),
            $this->subscriptionFactory->createContextBuilder(),
            $streamName,
            $readModel
        );
    }

    // todo remove all below except query scope
    public function provider(): ProjectorProvider
    {
        // make a subcontract from projectionProvider
        // where we can only access projector provider methods
        // another way is to dispatch events stop reset delete
        return new ProjectorProvider($this->subscriptionFactory->projectionProvider, $this->subscriptionFactory->jsonSerializer);
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

        return $this->subscriptionFactory->jsonSerializer->decode($projection->positions());
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

    /**
     * @throws ProjectionNotFound
     */
    private function tryRetrieveProjectionByName(string $projectionName): ProjectionModel
    {
        $projection = $this->subscriptionFactory->projectionProvider->retrieve($projectionName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $projection;
    }

    /**
     * @throws ProjectionFailed
     * @throws ProjectionNotFound
     */
    private function updateProjectionStatus(string $projectionName, ProjectionStatus $projectionStatus): void
    {
        try {
            $success = $this->subscriptionFactory->projectionProvider->updateProjection(
                $projectionName, status : $projectionStatus->value
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
