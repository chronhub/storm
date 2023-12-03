<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Contracts\Projector\EmitterProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\StreamCacheInterface;
use Chronhub\Storm\Projector\Scheme\EmitterProjectorScope;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Closure;

final readonly class ProjectEmitter implements EmitterProjector
{
    use InteractWithContext;
    use InteractWithPersistentProjection;

    public function __construct(
        protected EmitterSubscriptionInterface $subscription,
        protected ContextReaderInterface $context,
        private StreamCacheInterface $streamCache
    ) {
    }

    public function emit(DomainEvent $event): void
    {
        $streamName = new StreamName($this->getName());

        // First commit the stream name without the event
        if ($this->streamNotEmittedAndNotExists($streamName)) {
            $this->chronicler()->firstCommit(new Stream($streamName));

            $this->subscription->eventEmitted();
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

    protected function getScope(): EmitterProjectorScopeInterface
    {
        $userScope = $this->context->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new EmitterProjectorScope(
            $this, $this->subscription->clock(), fn (): string => $this->subscription->currentStreamName()
        );
    }

    private function streamNotEmittedAndNotExists(StreamName $streamName): bool
    {
        return ! $this->subscription->wasEmitted() && ! $this->chronicler()->hasStream($streamName);
    }

    private function streamIsCachedOrExists(StreamName $streamName): bool
    {
        if ($this->streamCache->has($streamName->name)) {
            return true;
        }

        $this->streamCache->push($streamName->name);

        return $this->chronicler()->hasStream($streamName);
    }

    private function chronicler(): Chronicler
    {
        return $this->subscription->chronicler();
    }
}
