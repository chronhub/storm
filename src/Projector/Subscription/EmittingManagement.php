<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\EmittedStreamCache;
use Chronhub\Storm\Contracts\Projector\EmitterManagement;
use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Projector\Stream\EmittedStream;
use Chronhub\Storm\Projector\Subscription\Notification\CurrentStatus;
use Chronhub\Storm\Projector\Subscription\Notification\EventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;

final readonly class EmittingManagement implements EmitterManagement
{
    use InteractWithManagement;

    public function __construct(
        protected NotificationHub $hub,
        protected Chronicler $chronicler,
        protected ProjectionRepository $repository,
        private EmittedStreamCache $streamCache,
        private EmittedStream $emittedStream,
    ) {
        HookHandler::subscribe($hub, $this);
    }

    public function emit(DomainEvent $event): void
    {
        $streamName = new StreamName($this->getName());

        // First commit the stream name without the event
        if ($this->streamNotEmittedAndNotExists($streamName)) {
            $this->chronicler->firstCommit(new Stream($streamName));

            $this->emittedStream->emitted();
        }

        // Append the stream with the event
        $this->linkTo($this->getName(), $event);
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $newStreamName = new StreamName($streamName);

        $stream = new Stream($newStreamName, [$event]);

        $this->streamIsCachedOrExists($newStreamName)
            ? $this->chronicler->amend($stream)
            : $this->chronicler->firstCommit($stream);
    }

    public function rise(): void
    {
        $this->mountProjection();

        $this->hub->notify(EventStreamDiscovered::class);

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionResult());
    }

    public function revise(): void
    {
        $this->resetState();

        $this->repository->reset($this->getProjectionResult(), $this->hub->expect(CurrentStatus::class));

        $this->deleteStream();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->hub->notify(SprintStopped::class);

        $this->resetState();
    }

    private function streamNotEmittedAndNotExists(StreamName $streamName): bool
    {
        return ! $this->emittedStream->wasEmitted()
            && ! $this->chronicler->hasStream($streamName);
    }

    private function streamIsCachedOrExists(StreamName $streamName): bool
    {
        if ($this->streamCache->has($streamName->name)) {
            return true;
        }

        $this->streamCache->push($streamName->name);

        return $this->chronicler->hasStream($streamName);
    }

    private function deleteStream(): void
    {
        try {
            $streamName = new StreamName($this->repository->projectionName());

            $this->chronicler->delete($streamName);
        } catch (StreamNotFound) {
            // ignore
        }

        $this->emittedStream->unlink();
    }
}
