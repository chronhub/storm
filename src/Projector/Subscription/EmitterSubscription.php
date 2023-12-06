<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Projector\EmitterProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\StreamCacheInterface;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\ProvideActivities;
use Chronhub\Storm\Projector\Scheme\EmitterProjectorScope;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\RunProjection;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Closure;

final class EmitterSubscription implements EmitterSubscriptionInterface
{
    use InteractWithPersistentSubscription;
    use InteractWithSubscription;

    private bool $isStreamFixed = false;

    public function __construct(
        protected readonly GenericSubscription $subscription,
        protected ProjectionRepositoryInterface $repository,
        protected EventCounter $eventCounter,
        private readonly StreamCacheInterface $streamCache
    ) {
    }

    public function start(bool $keepRunning): void
    {
        if (! $this->context()->queryFilter() instanceof ProjectionQueryFilter) {
            throw new RuntimeException('Emitter subscription requires a projection query filter');
        }

        $this->subscription->start($keepRunning);

        $project = new RunProjection($this, $this->newWorkflow());

        $project->beginCycle();
    }

    public function emit(DomainEvent $event): void
    {
        $streamName = new StreamName($this->getName());

        // First commit the stream name without the event
        if ($this->streamNotEmittedAndNotExists($streamName)) {
            $this->chronicler()->firstCommit(new Stream($streamName));

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
            ? $this->chronicler()->amend($stream)
            : $this->chronicler()->firstCommit($stream);
    }

    public function rise(): void
    {
        $this->mountProjection();

        $this->streamManager()->discover($this->context()->queries());

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionDetail(), null);
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->sprint()->stop();

        $this->streamManager()->resets();

        $this->initializeAgain();
    }

    public function revise(): void
    {
        $this->streamManager()->resets();

        $this->initializeAgain();

        $this->repository->reset($this->getProjectionDetail(), $this->currentStatus());

        $this->deleteStream();
    }

    private function streamNotEmittedAndNotExists(StreamName $streamName): bool
    {
        return ! $this->isStreamFixed && ! $this->chronicler()->hasStream($streamName);
    }

    private function streamIsCachedOrExists(StreamName $streamName): bool
    {
        if ($this->streamCache->has($streamName->name)) {
            return true;
        }

        $this->streamCache->push($streamName->name);

        return $this->chronicler()->hasStream($streamName);
    }

    private function deleteStream(): void
    {
        try {
            $this->chronicler()->delete(new StreamName($this->getName()));
        } catch (StreamNotFound) {
            // fail silently
        }

        $this->isStreamFixed = false;
    }

    protected function newWorkflow(): Workflow
    {
        $activities = ProvideActivities::persistent($this);

        return new Workflow($this, $activities);
    }

    public function getScope(): EmitterProjectorScopeInterface
    {
        $userScope = $this->context()->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new EmitterProjectorScope(
            $this, $this->subscription->clock(), fn (): string => $this->subscription->currentStreamName()
        );
    }
}
