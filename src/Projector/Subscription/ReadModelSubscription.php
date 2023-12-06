<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\ProvideActivities;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\ReadModelProjectorScope;
use Chronhub\Storm\Projector\Scheme\RunProjection;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Closure;

final class ReadModelSubscription implements ReadModelSubscriptionInterface
{
    use InteractWithPersistentSubscription;
    use InteractWithSubscription;

    public function __construct(
        protected readonly GenericSubscription $subscription,
        protected ProjectionRepositoryInterface $repository,
        protected EventCounter $eventCounter,
        private readonly ReadModel $readModel,
    ) {
    }

    public function start(bool $keepRunning): void
    {
        if (! $this->context()->queryFilter() instanceof ProjectionQueryFilter) {
            throw new RuntimeException('Read model subscription requires a projection query filter');
        }

        $this->subscription->start($keepRunning);

        $project = new RunProjection($this, $this->newWorkflow());

        $project->beginCycle();
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->streamManager()->discover($this->context()->queries());

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionDetail(), null);

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->streamManager()->resets();

        $this->initializeAgain();

        $this->repository->reset($this->getProjectionDetail(), $this->currentStatus());

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->sprint()->stop();

        $this->streamManager()->resets();

        $this->initializeAgain();
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    protected function newWorkflow(): Workflow
    {
        $activities = ProvideActivities::persistent($this);

        return new Workflow($this, $activities);
    }

    public function getScope(): ReadModelProjectorScopeInterface
    {
        $userScope = $this->context()->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new ReadModelProjectorScope(
            $this, $this->subscription->clock(), fn (): string => $this->subscription->currentStreamName()
        );
    }
}
