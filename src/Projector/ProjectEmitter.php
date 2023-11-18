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
        protected string $streamName
    ) {
        $this->streamCache = new StreamCache($subscription->option()->getCacheSize());
    }

    public function emit(DomainEvent $event): void
    {
        $streamName = new StreamName($this->streamName);

        /**
         * If the stream is not fixed, we must verify if the stream already exists.
         * Otherwise, we store it as the initial commit.
         */
        if (! $this->subscription->isFixed() && ! $this->chronicler->hasStream($streamName)) {
            $this->chronicler->firstCommit(new Stream($streamName));

            $this->subscription->fixe();
        }

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
        return new CastEmitter(
            $this, $this->subscription->clock(), fn (): ?string => $this->subscription->currentStreamName()
        );
    }

    /**
     * Verify whether the stream name is present in the cache and/or the event store.
     * If the stream is found in the in-memory cache, we assume it already exists in the event store.
     * Otherwise, we add the stream to the cache and check for its existence in the event store.
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
