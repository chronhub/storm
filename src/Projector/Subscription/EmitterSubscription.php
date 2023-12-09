<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Projector\EmitterProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriber;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\StateManagement;
use Chronhub\Storm\Contracts\Projector\StreamCacheInterface;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Scheme\EmitterProjectorScope;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\RunProjection;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Closure;

final class EmitterSubscription implements EmitterSubscriber
{
    use InteractWithPersistentSubscription;
    use InteractWithSubscription;

    private bool $isStreamFixed = false;

    public function __construct(
        protected readonly StateManagement $manager,
        protected readonly ProjectionRepositoryInterface $repository,
        protected readonly EventCounter $eventCounter,
        private readonly StreamCacheInterface $streamCache
    ) {
    }

    public function start(bool $keepRunning): void
    {
        if (! $this->manager->context()->queryFilter() instanceof ProjectionQueryFilter) {
            throw new RuntimeException('Emitter subscription requires a projection query filter');
        }

        $this->manager->start($keepRunning);

        $project = new RunProjection($this->newWorkflow(), $this->manager->sprint, $this);

        $project->beginCycle();
    }

    public function emit(DomainEvent $event): void
    {
        $streamName = new StreamName($this->getName());

        // First commit the stream name without the event
        if ($this->streamNotEmittedAndNotExists($streamName)) {
            $this->manager->chronicler->firstCommit(new Stream($streamName));

            $this->isStreamFixed = true;
        }

        // Append the stream with the event
        $this->linkTo($this->getName(), $event);
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $newStreamName = new StreamName($streamName);

        $stream = new Stream($newStreamName, [$event]);

        $this->streamIsCachedOrExists($newStreamName)
            ? $this->manager->chronicler->amend($stream)
            : $this->manager->chronicler->firstCommit($stream);
    }

    public function rise(): void
    {
        $this->mountProjection();

        $this->manager->streamBinder->discover($this->manager->context()->queries());

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionDetail(), null);
    }

    public function revise(): void
    {
        $this->manager->streamBinder->resets();

        $this->manager->initializeAgain();

        $this->repository->reset($this->getProjectionDetail(), $this->manager->currentStatus());

        $this->deleteStream();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->manager->sprint->stop();

        $this->manager->streamBinder->resets();

        $this->manager->initializeAgain();
    }

    private function streamNotEmittedAndNotExists(StreamName $streamName): bool
    {
        return ! $this->isStreamFixed && ! $this->manager->chronicler->hasStream($streamName);
    }

    private function streamIsCachedOrExists(StreamName $streamName): bool
    {
        if ($this->streamCache->has($streamName->name)) {
            return true;
        }

        $this->streamCache->push($streamName->name);

        return $this->manager->chronicler->hasStream($streamName);
    }

    private function deleteStream(): void
    {
        try {
            $this->manager->chronicler->delete(new StreamName($this->getName()));
        } catch (StreamNotFound) {
            // ignore
        }

        $this->isStreamFixed = false;
    }

    protected function newWorkflow(): Workflow
    {
        return new Workflow($this->manager, $this->getActivities());
    }

    public function getScope(): EmitterProjectorScopeInterface
    {
        $userScope = $this->manager->context->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new EmitterProjectorScope($this);
    }
}
