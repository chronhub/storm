<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\StreamCache;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Projector\Scheme\PersistentCaster;
use Chronhub\Storm\Contracts\Projector\ProjectionProjector;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;
use Chronhub\Storm\Contracts\Projector\PersistentProjectorCaster;

final readonly class ProjectProjection implements ProjectionProjector
{
    use InteractWithContext;
    use ProvidePersistentProjector;

    private StreamCache $streamCache;

    public function __construct(protected Context $context,
                                protected ProjectorRepository $repository,
                                protected Chronicler $chronicler,
                                protected string $streamName)
    {
        $this->streamCache = new StreamCache($context->option->getCacheSize());
    }

    public function emit(DomainEvent $event): void
    {
        $streamName = new StreamName($this->streamName);

        $this->persistIfStreamIsFirstCommit($streamName);

        $this->linkTo($this->streamName, $event);
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $newStreamName = new StreamName($streamName);

        $stream = new Stream($newStreamName, [$event]);

        $this->determineIfStreamAlreadyExists($newStreamName)
            ? $this->chronicler->amend($stream)
            : $this->chronicler->firstCommit($stream);
    }

    protected function getCaster(): PersistentProjectorCaster
    {
        return new PersistentCaster($this, $this->context->currentStreamName);
    }

    /**
     * Persist domain event if stream does not already exist
     * in the event store and not already set in the projection context
     */
    private function persistIfStreamIsFirstCommit(StreamName $streamName): void
    {
        if (! $this->context->isStreamCreated && ! $this->chronicler->hasStream($streamName)) {
            $this->chronicler->firstCommit(new Stream($streamName));

            $this->context->isStreamCreated = true;
        }
    }

    /**
     * Check if stream name already exists in cache and/or in the event store
     */
    private function determineIfStreamAlreadyExists(StreamName $streamName): bool
    {
        if ($this->streamCache->has($streamName->name)) {
            return true;
        }

        $this->streamCache->push($streamName->name);

        return $this->chronicler->hasStream($streamName);
    }
}
