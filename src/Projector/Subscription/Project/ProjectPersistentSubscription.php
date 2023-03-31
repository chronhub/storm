<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Project;

use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Projector\Scheme\StreamCache;
use Chronhub\Storm\Projector\InteractWithContext;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Projector\Scheme\PersistentCaster;
use Chronhub\Storm\Contracts\Projector\ContextBuilder;
use Chronhub\Storm\Contracts\Projector\ProjectionProjector;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\PersistentProjectorCaster;
use Chronhub\Storm\Contracts\Projector\PersistentViewSubscription;

final readonly class ProjectPersistentSubscription implements ProjectionProjector
{
    use InteractWithContext;
    use ProvidePersistentSubscription;

    private StreamCache $streamCache;

    public function __construct(
      protected PersistentViewSubscription $subscription,
      protected ContextBuilder $context,
      protected ProjectionRepository $repository,
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

    protected function getCaster(): PersistentProjectorCaster
    {
        return new PersistentCaster(
            $this, $this->subscription->clock(), $this->subscription->currentStreamName
        );
    }

    /**
     * Persist domain event if stream does not already exist
     * in the event store and not already set in the projection context
     */
    private function persistIfStreamIsFirstCommit(StreamName $streamName): void
    {
        if (! $this->subscription->isAttached() && ! $this->chronicler->hasStream($streamName)) {
            $this->chronicler->firstCommit(new Stream($streamName));

            $this->subscription->attach();
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
