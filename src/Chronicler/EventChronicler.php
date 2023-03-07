<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler;

use Generator;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Contracts\Tracker\Listener;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Tracker\StreamTracker;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Chronicler\TransactionalChronicler;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamTracker;

class EventChronicler implements EventableChronicler
{
    public function __construct(
        protected Chronicler|TransactionalChronicler $chronicler,
        protected StreamTracker|TransactionalStreamTracker $tracker)
    {
        ProvideEvents::withEvent($this->chronicler, $this->tracker);
    }

    public function firstCommit(Stream $stream): void
    {
        $story = $this->tracker->newStory(self::FIRST_COMMIT_EVENT);

        $story->deferred(fn (): Stream => $stream);

        $this->tracker->disclose($story);

        if ($story->hasStreamAlreadyExits()) {
            throw $story->exception();
        }
    }

    public function amend(Stream $stream): void
    {
        $story = $this->tracker->newStory(self::PERSIST_STREAM_EVENT);

        $story->deferred(fn (): Stream => $stream);

        $this->tracker->disclose($story);

        if ($story->hasStreamNotFound() || $story->hasConcurrency()) {
            throw $story->exception();
        }
    }

    public function delete(StreamName $streamName): void
    {
        $story = $this->tracker->newStory(self::DELETE_STREAM_EVENT);

        $story->deferred(fn (): StreamName => $streamName);

        $this->tracker->disclose($story);

        if ($story->hasStreamNotFound()) {
            throw $story->exception();
        }
    }

    public function retrieveAll(StreamName $streamName, AggregateIdentity $aggregateId, string $direction = 'asc'): Generator
    {
        $eventName = 'asc' === $direction ? self::ALL_STREAM_EVENT : self::ALL_REVERSED_STREAM_EVENT;

        $story = $this->tracker->newStory($eventName);

        $story->deferred(fn (): array => [$streamName, $aggregateId, $direction]);

        $this->tracker->disclose($story);

        if ($story->hasStreamNotFound()) {
            throw $story->exception();
        }

        /** @var Generator $events */
        $events = $story->promise()->events();

        return $events;
    }

    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator
    {
        $story = $this->tracker->newStory(self::FILTERED_STREAM_EVENT);

        $story->deferred(fn (): array => [$streamName, $queryFilter]);

        $this->tracker->disclose($story);

        if ($story->hasStreamNotFound()) {
            throw $story->exception();
        }

        /** @var Generator $events */
        $events = $story->promise()->events();

        return $events;
    }

    public function filterStreamNames(StreamName ...$streamNames): array
    {
        $story = $this->tracker->newStory(self::FILTER_STREAM_NAMES);

        $story->deferred(fn (): array => $streamNames);

        $this->tracker->disclose($story);

        return $story->promise();
    }

    public function filterCategoryNames(string ...$categoryNames): array
    {
        $story = $this->tracker->newStory(self::FILTER_CATEGORY_NAMES);

        $story->deferred(fn (): array => $categoryNames);

        $this->tracker->disclose($story);

        return $story->promise();
    }

    public function hasStream(StreamName $streamName): bool
    {
        $story = $this->tracker->newStory(self::HAS_STREAM_EVENT);

        $story->deferred(fn (): StreamName => $streamName);

        $this->tracker->disclose($story);

        return $story->promise();
    }

    public function subscribe(string $eventName, callable $eventContext, int $priority = 0): Listener
    {
        return $this->tracker->watch($eventName, $eventContext, $priority);
    }

    public function unsubscribe(Listener ...$eventSubscribers): void
    {
        foreach ($eventSubscribers as $eventSubscriber) {
            $this->tracker->forget($eventSubscriber);
        }
    }

    public function getEventStreamProvider(): EventStreamProvider
    {
        return $this->chronicler->getEventStreamProvider();
    }

    public function innerChronicler(): Chronicler
    {
        return $this->chronicler;
    }
}
