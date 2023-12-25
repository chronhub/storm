<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\EmitterManagement;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\StreamCache;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Stream\EmittedStream;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;

final readonly class EmittingManagement implements EmitterManagement
{
    use InteractWithManagement;

    public function __construct(
        protected Subscriptor $subscriptor,
        protected Chronicler $chronicler,
        protected ProjectionRepository $repository,
        private StreamCache $streamCache,
        private EmittedStream $emittedStream
    ) {
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

        $this->subscriptor->discoverStreams();

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionResult());
    }

    public function revise(): void
    {
        $this->subscriptor->resetCheckpoint();
        $this->subscriptor->initializeAgain();
        $this->repository->reset($this->getProjectionResult(), $this->subscriptor->currentStatus());

        $this->deleteStream();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->subscriptor->stop();
        $this->subscriptor->resetCheckpoint();
        $this->subscriptor->initializeAgain();
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

        $this->emittedStream->reset();
    }
}
