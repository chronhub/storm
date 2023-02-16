<?php

declare(strict_types=1);

namespace Chronhub\Storm\Publisher;

use Illuminate\Support\Collection;
use Chronhub\Storm\Contracts\Tracker\Listener;
use Chronhub\Storm\Contracts\Tracker\StreamStory;
use Chronhub\Storm\Contracts\Chronicler\EventPublisher;
use Chronhub\Storm\Contracts\Chronicler\StreamSubscriber;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;

final class EventPublisherSubscriber implements StreamSubscriber
{
    /**
     * @var array<Listener>
     */
    private array $streamSubscribers = [];

    public function __construct(private readonly EventPublisher $publisher)
    {
    }

    public function attachToChronicler(EventableChronicler $chronicler): void
    {
        $this->subscribeOnFirstCommit($chronicler);

        $this->subscribeOnPersist($chronicler);

        if ($chronicler instanceof TransactionalEventableChronicler) {
            $this->subscribeOnTransaction($chronicler);
        }
    }

    public function detachFromChronicler(EventableChronicler $chronicler): void
    {
        $chronicler->unsubscribe(...$this->streamSubscribers);
    }

    private function subscribeOnFirstCommit(EventableChronicler $chronicler): void
    {
        $this->streamSubscribers[] = $chronicler->subscribe(
            $chronicler::FIRST_COMMIT_EVENT,
            function (StreamStory $story) use ($chronicler): void {
                $streamEvents = new Collection($story->promise()->events());

                if (! $this->inTransaction($chronicler)) {
                    if (! $story->hasStreamAlreadyExits()) {
                        $this->publisher->publish($streamEvents);
                    }
                } else {
                    $this->publisher->record($streamEvents);
                }
            },
        );
    }

    private function subscribeOnPersist(EventableChronicler $chronicler): void
    {
        $this->streamSubscribers[] = $chronicler->subscribe(
            $chronicler::PERSIST_STREAM_EVENT,
            function (StreamStory $story) use ($chronicler): void {
                $streamEvents = new Collection($story->promise()->events());

                if (! $this->inTransaction($chronicler)) {
                    if (! $story->hasStreamNotFound() && ! $story->hasConcurrency()) {
                        $this->publisher->publish($streamEvents);
                    }
                } else {
                    $this->publisher->record($streamEvents);
                }
            },
        );
    }

    private function subscribeOnTransaction(EventableChronicler&TransactionalEventableChronicler $chronicler): void
    {
        $this->streamSubscribers[] = $chronicler->subscribe(
            $chronicler::COMMIT_TRANSACTION_EVENT,
            function (): void {
                $pendingEvents = $this->publisher->pull();

                $this->publisher->publish($pendingEvents);
            },
        );

        $this->streamSubscribers[] = $chronicler->subscribe(
            $chronicler::ROLLBACK_TRANSACTION_EVENT,
            function (): void {
                $this->publisher->flush();
            },
        );
    }

    private function inTransaction(EventableChronicler $chronicler): bool
    {
        return $chronicler instanceof TransactionalChronicler && $chronicler->inTransaction();
    }
}
