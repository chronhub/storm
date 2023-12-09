<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\ReadModelProjectorScope;
use Chronhub\Storm\Projector\Scheme\RunProjection;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Closure;

final readonly class ReadModelSubscription implements ReadModelSubscriber
{
    use InteractWithPersistentSubscription;
    use InteractWithSubscription;

    public function __construct(
        protected Beacon $manager,
        protected ProjectionRepositoryInterface $repository,
        protected EventCounter $eventCounter,
        private ReadModel $readModel,
    ) {
    }

    public function start(bool $keepRunning): void
    {
        if (! $this->manager->context->queryFilter() instanceof ProjectionQueryFilter) {
            throw new RuntimeException('Read model subscription requires a projection query filter');
        }

        $this->manager->start($keepRunning);

        $project = new RunProjection($this->newWorkflow(), $this->manager->sprint, $this);

        $project->beginCycle();
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->manager->streamBinder->discover(
            $this->manager->context->queries()
        );

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionDetail(), null);

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->manager->streamBinder->resets();

        $this->manager->initializeAgain();

        $this->repository->reset($this->getProjectionDetail(), $this->manager->currentStatus());

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->manager->sprint->stop();

        $this->manager->streamBinder->resets();

        $this->manager->initializeAgain();
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    protected function newWorkflow(): Workflow
    {
        return new Workflow($this->manager, $this->getActivities());
    }

    public function getScope(): ReadModelProjectorScopeInterface
    {
        $userScope = $this->manager->context()->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new ReadModelProjectorScope($this);
    }
}
