<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Chronicler\EventChronicler;
use Chronhub\Storm\Chronicler\Exceptions\ConcurrencyException;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\TrackStream;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Tracker\StreamStory;
use Chronhub\Storm\Contracts\Tracker\StreamTracker;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(EventChronicler::class)]
class EventChroniclerTest extends UnitTestCase
{
    private Chronicler|MockObject $chronicler;

    private StreamTracker|MockObject $tracker;

    private Stream $stream;

    public function setUp(): void
    {
        $this->chronicler = $this->createMock(Chronicler::class);
        $this->tracker = new TrackStream();
        $this->stream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'bar']),
        ]);
    }

    public function testFirstCommitStream(): void
    {
        $this->chronicler->expects($this->once())->method('firstCommit')->with($this->stream);

        $this->tracker->watch(EventableChronicler::FIRST_COMMIT_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::FIRST_COMMIT_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $this->stream);
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->firstCommit($this->stream);
    }

    public function testExceptionRaisedOnFirstCommit(): void
    {
        $this->expectException(StreamAlreadyExists::class);

        $this->chronicler->expects($this->once())->method('firstCommit')->with($this->stream);

        $this->tracker->watch(EventableChronicler::FIRST_COMMIT_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::FIRST_COMMIT_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $this->stream);
                $story->withRaisedException(StreamAlreadyExists::withStreamName($this->stream->name()));
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->firstCommit($this->stream);
    }

    public function testAmendStream(): void
    {
        $this->chronicler->expects($this->once())->method('amend')->with($this->stream);

        $this->tracker->watch(EventableChronicler::PERSIST_STREAM_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::PERSIST_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $this->stream);
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->amend($this->stream);
    }

    public function testExceptionRaisedOnAmendStream(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->chronicler->expects($this->once())->method('amend')->with($this->stream);

        $this->tracker->watch(EventableChronicler::PERSIST_STREAM_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::PERSIST_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $this->stream);
                $story->withRaisedException(StreamNotFound::withStreamName($this->stream->name()));
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->amend($this->stream);
    }

    public function testConcurrencyExceptionRaisedOnAmendStream(): void
    {
        $this->expectException(ConcurrencyException::class);

        $this->chronicler->expects($this->once())->method('amend')->with($this->stream);

        $this->tracker->watch(EventableChronicler::PERSIST_STREAM_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::PERSIST_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $this->stream);
                $story->withRaisedException(new ConcurrencyException('concurrency exception'));
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->amend($this->stream);
    }

    public function testDeleteStream(): void
    {
        $this->chronicler->expects($this->once())->method('delete')->with($this->stream->name());

        $this->tracker->watch(EventableChronicler::DELETE_STREAM_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::DELETE_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $this->stream->name());
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->delete($this->stream->name());
    }

    public function testStreamNotFoundRaisedOnDeleteStream(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->chronicler->expects($this->once())->method('delete')->with($this->stream->name());

        $this->tracker->watch(EventableChronicler::DELETE_STREAM_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::DELETE_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $this->stream->name());
                $story->withRaisedException(StreamNotFound::withStreamName($this->stream->name()));
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->delete($this->stream->name());
    }

    public function testRetrieveAlStreamEvents(): void
    {
        $args = [$this->stream->name(), V4AggregateId::create(), 'asc'];

        $this->chronicler->expects($this->once())
            ->method('retrieveAll')
            ->with(...$args)
            ->will($this->returnCallback(function () {
                yield from $this->stream->events();
            }));

        $this->tracker->watch(EventableChronicler::ALL_STREAM_EVENT,
            function (StreamStory $story) use ($args): void {
                $this->assertEquals(EventableChronicler::ALL_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $args);
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $streamEvents = $eventStore->retrieveAll(...$args);

        $this->assertEquals($this->stream->events()->current(), $streamEvents->current());
    }

    public function testStreamNotFoundRaisedWhenRetrieveAllStreamEvents(): void
    {
        $this->expectException(StreamNotFound::class);

        $args = [$this->stream->name(), V4AggregateId::create(), 'asc'];

        $this->chronicler->expects($this->once())
            ->method('retrieveAll')
            ->with(...$args);

        $this->tracker->watch(EventableChronicler::ALL_STREAM_EVENT,
            function (StreamStory $story) use ($args): void {
                $this->assertEquals(EventableChronicler::ALL_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $args);
                $story->withRaisedException(StreamNotFound::withStreamName($args[0]));
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->retrieveAll(...$args)->current();
    }

    public function testRetrieveFilteredStreamEvents(): void
    {
        $queryFilter = $this->createMock(QueryFilter::class);
        $args = [$this->stream->name(), $queryFilter];

        $this->chronicler->expects($this->once())
            ->method('retrieveFiltered')
            ->with(...$args)
            ->will($this->returnCallback(function () {
                yield from $this->stream->events();
            }));

        $this->tracker->watch(EventableChronicler::FILTERED_STREAM_EVENT,
            function (StreamStory $story) use ($args): void {
                $this->assertEquals(EventableChronicler::FILTERED_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $args);
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $streamEvents = $eventStore->retrieveFiltered(...$args);

        $this->assertEquals($this->stream->events()->current(), $streamEvents->current());
    }

    public function testStreamNotFoundRaisedWhenRetrieveFilteredStreamEvents(): void
    {
        $this->expectException(StreamNotFound::class);

        $stream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'bar']),
        ]);

        $queryFilter = $this->createMock(QueryFilter::class);
        $args = [$stream->name(), $queryFilter];

        $this->chronicler->expects($this->once())
            ->method('retrieveFiltered')
            ->with(...$args);

        $this->tracker->watch(EventableChronicler::FILTERED_STREAM_EVENT,
            function (StreamStory $story) use ($args): void {
                $this->assertEquals(EventableChronicler::FILTERED_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $args);
                $story->withRaisedException(StreamNotFound::withStreamName($args[0]));
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->retrieveFiltered(...$args)->current();
    }

    public function testFilterStreamNames(): void
    {
        $args = [new StreamName('foo'), new StreamName('bar'), new StreamName('baz')];

        $this->chronicler->expects($this->once())
            ->method('filterStreamNames')
            ->with(...$args)
            ->will($this->returnValue([$args[0], $args[2]]));

        $this->tracker->watch(EventableChronicler::FILTER_STREAM_NAMES,
            function (StreamStory $story) use ($args): void {
                $this->assertEquals(EventableChronicler::FILTER_STREAM_NAMES, $story->currentEvent());
                $this->assertEquals($story->promise(), $args);
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $streamNames = $eventStore->filterStreamNames(...$args);

        $this->assertEquals([new StreamName('foo'), new StreamName('baz')], $streamNames);
    }

    public function testFilterCategoryNames(): void
    {
        $args = ['foo', 'bar', 'baz'];

        $this->chronicler->expects($this->once())
            ->method('filterCategoryNames')
            ->with(...$args)
            ->will($this->returnValue([$args[0], $args[2]]));

        $this->tracker->watch(EventableChronicler::FILTER_CATEGORY_NAMES,
            function (StreamStory $story) use ($args): void {
                $this->assertEquals(EventableChronicler::FILTER_CATEGORY_NAMES, $story->currentEvent());
                $this->assertEquals($story->promise(), $args);
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $streamNames = $eventStore->filterCategoryNames(...$args);

        $this->assertEquals(['foo', 'baz'], $streamNames);
    }

    #[DataProvider('provideBoolean')]
    public function testCheckStreamExists(bool $streamExists): void
    {
        $streamName = new StreamName('foo');

        $this->chronicler->expects($this->once())
            ->method('hasStream')
            ->with($streamName)
            ->willReturn($streamExists);

        $this->tracker->watch(EventableChronicler::FILTER_CATEGORY_NAMES,
            function (StreamStory $story) use ($streamExists): void {
                $this->assertEquals(EventableChronicler::FILTER_CATEGORY_NAMES, $story->currentEvent());
                $this->assertEquals($story->promise(), $streamExists);
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $hasStream = $eventStore->hasStream($streamName);

        $this->assertSame($streamExists, $hasStream);
    }

    public function testInnerEventStoreGetter(): void
    {
        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $this->assertSame($this->chronicler, $eventStore->innerChronicler());
    }

    public function testEventStreamProviderGetter(): void
    {
        $eventStreamProvider = $this->createMock(EventStreamProvider::class);

        $this->chronicler
            ->expects($this->once())
            ->method('getEventStreamProvider')
            ->willReturn($eventStreamProvider);

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $this->assertSame($eventStreamProvider, $eventStore->getEventStreamProvider());
    }

    public function testSubscribeToEventStore(): void
    {
        $alteredStream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'baz']),
        ]);

        $this->chronicler->expects($this->once())->method('firstCommit')->with($alteredStream);

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->subscribe(EventableChronicler::FIRST_COMMIT_EVENT,
            function (StreamStory $story) use ($alteredStream): void {
                $this->assertEquals(EventableChronicler::FIRST_COMMIT_EVENT, $story->currentEvent());

                $this->assertEquals($story->promise(), $this->stream);

                $story->deferred(fn () => $alteredStream);
            }, 100);

        $eventStore->firstCommit($this->stream);
    }

    public function testItUnsubscribeFromEventStore(): void
    {
        $stream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'bar']),
        ]);

        $alteredStream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'baz']),
        ]);

        $this->chronicler->expects($this->once())->method('firstCommit')->with($stream);

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $streamSubscriber = $eventStore->subscribe(EventableChronicler::FIRST_COMMIT_EVENT,
            function (StreamStory $story) use ($alteredStream, $stream): void {
                $this->assertEquals(EventableChronicler::FIRST_COMMIT_EVENT, $story->currentEvent());

                $this->assertEquals($story->promise(), $stream);

                $story->deferred(fn () => $alteredStream);
            }, 100);

        $eventStore->unsubscribe($streamSubscriber);

        $eventStore->firstCommit($stream);
    }

    public static function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
