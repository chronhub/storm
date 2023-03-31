<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Throwable;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectorManager;
use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ProjectionProjector;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Projector\Exceptions\InMemoryProjectionFailed;
use Chronhub\Storm\Projector\Subscription\Project\ProjectLiveSubscription;
use Chronhub\Storm\Projector\Subscription\Project\ProjectReadModelSubscription;
use Chronhub\Storm\Projector\Subscription\Project\ProjectPersistentSubscription;

final readonly class SubscriptionManager implements ProjectorManager
{
    public function __construct(private SubscriptionFactory $subscriptionFactory)
    {
    }

    public function projectQuery(array $options = []): QueryProjector
    {
        return new ProjectLiveSubscription(
            $this->subscriptionFactory->createLiveSubscription($options),
            $this->subscriptionFactory->createContextBuilder(),
            $this->subscriptionFactory->chronicler
        );
    }

    public function projectProjection(string $streamName, array $options = []): ProjectionProjector
    {
        $subscription = $this->subscriptionFactory->createPersistentSubscription($options);

        return new ProjectPersistentSubscription(
            $subscription,
            $this->subscriptionFactory->createContextBuilder(),
            $this->subscriptionFactory->createSubscriptionManagement($subscription, $streamName, null),
            $this->subscriptionFactory->chronicler,
            $streamName
        );
    }

    public function projectReadModel(string $streamName, ReadModel $readModel, array $options = []): ReadModelProjector
    {
        $subscription = $this->subscriptionFactory->createReadModelSubscription($options);

        return new ProjectReadModelSubscription(
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
        return $this->subscriptionFactory->projectionProvider->projectionExists($projectionName);
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
     * @throws InMemoryProjectionFailed
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
