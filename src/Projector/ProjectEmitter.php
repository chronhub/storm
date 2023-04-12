<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\EmitterCasterInterface;
use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Projector\Scheme\CastEmitter;
use Chronhub\Storm\Projector\Scheme\StreamCache;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;

final readonly class ProjectEmitter implements EmitterProjector
{
    use InteractWithContext;
    use InteractWithPersistentProjection;

    private StreamCache $streamCache;

    public function __construct(
      protected EmitterSubscriptionInterface $subscription,
      protected ContextInterface $context,
      protected Chronicler $chronicler,
      protected string $streamName)
    {
        $this->streamCache = new StreamCache($subscription->option()->getCacheSize());
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

    protected function getCaster(): EmitterCasterInterface
    {
        return new CastEmitter($this, $this->subscription->clock(), $this->subscription->currentStreamName);
    }

    /**
     * Persist domain event if stream does not already exist
     * in the event store and not already set in the projection context
     */
    private function persistIfStreamIsFirstCommit(StreamName $streamName): void
    {
        if (! $this->subscription->isJoined() && ! $this->chronicler->hasStream($streamName)) {
            $this->chronicler->firstCommit(new Stream($streamName));

            $this->subscription->join();
        }
    }

    /**
     * Check if stream name already exists in cache and/or in the event store
     * if the in-memory cache has the stream, we assume it already exists in the event store
     * otherwise, we push the stream into it and check if it exists in the event store
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
