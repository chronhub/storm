<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler;

use Chronhub\Storm\Chronicler\Exceptions\ConcurrencyException;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Chronhub\Storm\Chronicler\Exceptions\TransactionNotStarted;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;
use Chronhub\Storm\Contracts\Tracker\StreamStory;
use Chronhub\Storm\Contracts\Tracker\StreamTracker;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamStory;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamTracker;
use Chronhub\Storm\Stream\Stream;

final class ProvideEvents
{
    public static function withEvent(Chronicler $chronicler, StreamTracker $tracker): void
    {
        $tracker->watch(
            EventableChronicler::FIRST_COMMIT_EVENT,
            static function (StreamStory $story) use ($chronicler): void {
                try {
                    $chronicler->firstCommit($story->promise());
                } catch (StreamAlreadyExists $exception) {
                    $story->withRaisedException($exception);
                }
            }
        );

        $tracker->watch(
            EventableChronicler::PERSIST_STREAM_EVENT,
            static function (StreamStory $story) use ($chronicler): void {
                try {
                    $chronicler->amend($story->promise());
                } catch (StreamNotFound|ConcurrencyException $exception) {
                    $story->withRaisedException($exception);
                }
            }
        );

        $tracker->watch(
            EventableChronicler::DELETE_STREAM_EVENT,
            static function (StreamStory $story) use ($chronicler): void {
                try {
                    $chronicler->delete($story->promise());
                } catch (StreamNotFound $exception) {
                    $story->withRaisedException($exception);
                }
            }
        );

        foreach ([EventableChronicler::ALL_STREAM_EVENT, EventableChronicler::ALL_REVERSED_STREAM_EVENT] as $eventName) {
            $tracker->watch(
                $eventName,
                static function (StreamStory $story) use ($chronicler): void {
                    try {
                        [$streamName, $aggregateId, $direction] = $story->promise();

                        $streamEvents = $chronicler->retrieveAll($streamName, $aggregateId, $direction);

                        $newStream = new Stream($streamName, $streamEvents);

                        $story->deferred(static fn (): Stream => $newStream);
                    } catch (StreamNotFound $exception) {
                        $story->withRaisedException($exception);
                    }
                }
            );
        }

        $tracker->watch(
            EventableChronicler::FILTERED_STREAM_EVENT,
            static function (StreamStory $story) use ($chronicler): void {
                try {
                    [$streamName, $queryFilter] = $story->promise();

                    $streamEvents = $chronicler->retrieveFiltered($streamName, $queryFilter);

                    $newStream = new Stream($streamName, $streamEvents);

                    $story->deferred(static fn (): Stream => $newStream);
                } catch (StreamNotFound $exception) {
                    $story->withRaisedException($exception);
                }
            }
        );

        $tracker->watch(
            EventableChronicler::FILTER_STREAM_NAMES,
            static function (StreamStory $story) use ($chronicler): void {
                $streamNames = $chronicler->filterStreamNames(...$story->promise());

                $story->deferred(static fn (): array => $streamNames);
            }
        );

        $tracker->watch(
            EventableChronicler::FILTER_CATEGORY_NAMES,
            static function (StreamStory $story) use ($chronicler): void {
                $categoryNames = $chronicler->filterCategoryNames(...$story->promise());

                $story->deferred(static fn (): array => $categoryNames);
            }
        );

        $tracker->watch(
            EventableChronicler::HAS_STREAM_EVENT,
            static function (StreamStory $story) use ($chronicler): void {
                $streamExists = $chronicler->hasStream($story->promise());

                $story->deferred(static fn (): bool => $streamExists);
            }
        );
    }

    public static function withTransactionalEvent(TransactionalChronicler $chronicler,
                                                  TransactionalStreamTracker $tracker): void
    {
        $tracker->watch(
            TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT,
            static function (TransactionalStreamStory $story) use ($chronicler): void {
                try {
                    $chronicler->beginTransaction();
                } catch (TransactionAlreadyStarted $exception) {
                    $story->withRaisedException($exception);
                }
            }
        );

        $tracker->watch(
            TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT,
            static function (TransactionalStreamStory $story) use ($chronicler): void {
                try {
                    $chronicler->commitTransaction();
                } catch (TransactionNotStarted $exception) {
                    $story->withRaisedException($exception);
                }
            }
        );

        $tracker->watch(
            TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT,
            static function (TransactionalStreamStory $story) use ($chronicler): void {
                try {
                    $chronicler->rollbackTransaction();
                } catch (TransactionNotStarted $exception) {
                    $story->withRaisedException($exception);
                }
            }
        );
    }
}
