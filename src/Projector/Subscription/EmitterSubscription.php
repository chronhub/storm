<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\EmitterProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriber;
use Chronhub\Storm\Projector\Scheme\EmitterProjectorScope;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Closure;

final readonly class EmitterSubscription implements EmitterSubscriber
{
    use InteractWithPersistentSubscription;

    public function __construct(
        public Subscription $subscription,
        public EmitterManagement $management,
    ) {
    }

    public function emit(DomainEvent $event): void
    {
        $streamName = new StreamName($this->management->getName());

        // First commit the stream name without the event
        if ($this->streamNotEmittedAndNotExists($streamName)) {
            $this->subscription->chronicler->firstCommit(new Stream($streamName));

            $this->subscription->emittedStream->emitted();
        }

        // Append the stream with the event
        $this->linkTo($this->management->getName(), $event);
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $newStreamName = new StreamName($streamName);

        $stream = new Stream($newStreamName, [$event]);

        $this->streamIsCachedOrExists($newStreamName)
            ? $this->subscription->chronicler->amend($stream)
            : $this->subscription->chronicler->firstCommit($stream);
    }

    private function streamNotEmittedAndNotExists(StreamName $streamName): bool
    {
        return ! $this->subscription->emittedStream->wasEmitted()
            && ! $this->subscription->chronicler->hasStream($streamName);
    }

    private function streamIsCachedOrExists(StreamName $streamName): bool
    {
        if ($this->subscription->streamCache->has($streamName->name)) {
            return true;
        }

        $this->subscription->streamCache->push($streamName->name);

        return $this->subscription->chronicler->hasStream($streamName);
    }

    public function getScope(): EmitterProjectorScopeInterface
    {
        $userScope = $this->subscription->context->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new EmitterProjectorScope($this->management, $this);
    }
}
