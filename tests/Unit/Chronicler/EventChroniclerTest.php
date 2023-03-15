<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Generator;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Chronicler\TrackStream;
use Chronhub\Storm\Aggregate\V4AggregateId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Chronicler\EventChronicler;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Contracts\Tracker\StreamStory;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Tracker\StreamTracker;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\ConcurrencyException;

#[CoversClass(EventChronicler::class)]
class EventChroniclerTest extends UnitTestCase
{
    private Chronicler|MockObject $chronicler;

    private StreamTracker|MockObject $tracker;

    public function setUp(): void
    {
        parent::setUp();

        $this->chronicler = $this->createMock(Chronicler::class);
        $this->tracker = new TrackStream();
    }

    #[Test]
    public function it_create_first_commit_stream(): void
    {
        $stream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'bar']),
        ]);

        $this->chronicler->expects($this->once())->method('firstCommit')->with($stream);

        $this->tracker->watch(EventableChronicler::FIRST_COMMIT_EVENT,
            function (StreamStory $story) use ($stream): void {
                $this->assertEquals(EventableChronicler::FIRST_COMMIT_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $stream);
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->firstCommit($stream);
    }

    #[Test]
    public function it_raise_exception_on_first_commit(): void
    {
        $this->expectException(StreamAlreadyExists::class);

        $stream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'bar']),
        ]);

        $this->chronicler->expects($this->once())->method('firstCommit')->with($stream);

        $this->tracker->watch(EventableChronicler::FIRST_COMMIT_EVENT,
            function (StreamStory $story) use ($stream): void {
                $this->assertEquals(EventableChronicler::FIRST_COMMIT_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $stream);
                $story->withRaisedException(StreamAlreadyExists::withStreamName($stream->name()));
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->firstCommit($stream);
    }

    #[Test]
    public function it_amend_stream(): void
    {
        $stream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'bar']),
        ]);

        $this->chronicler->expects($this->once())->method('amend')->with($stream);

        $this->tracker->watch(EventableChronicler::PERSIST_STREAM_EVENT,
            function (StreamStory $story) use ($stream): void {
                $this->assertEquals(EventableChronicler::PERSIST_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $stream);
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->amend($stream);
    }

    #[Test]
    public function it_raise_stream_not_found_exception_on_amend_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $stream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'bar']),
        ]);

        $this->chronicler->expects($this->once())->method('amend')->with($stream);

        $this->tracker->watch(EventableChronicler::PERSIST_STREAM_EVENT,
            function (StreamStory $story) use ($stream): void {
                $this->assertEquals(EventableChronicler::PERSIST_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $stream);
                $story->withRaisedException(StreamNotFound::withStreamName($stream->name()));
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->amend($stream);
    }

    #[Test]
    public function it_raise_concurrency_exception_on_amend_stream(): void
    {
        $this->expectException(ConcurrencyException::class);

        $stream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'bar']),
        ]);

        $this->chronicler->expects($this->once())->method('amend')->with($stream);

        $this->tracker->watch(EventableChronicler::PERSIST_STREAM_EVENT,
            function (StreamStory $story) use ($stream): void {
                $this->assertEquals(EventableChronicler::PERSIST_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $stream);
                $story->withRaisedException(new ConcurrencyException('concurrency exception'));
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->amend($stream);
    }

    #[Test]
    public function it_delete_stream(): void
    {
        $stream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'bar']),
        ]);

        $this->chronicler->expects($this->once())->method('delete')->with($stream->name());

        $this->tracker->watch(EventableChronicler::DELETE_STREAM_EVENT,
            function (StreamStory $story) use ($stream): void {
                $this->assertEquals(EventableChronicler::DELETE_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $stream->name());
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->delete($stream->name());
    }

    #[Test]
    public function it_raise_stream_not_found_on_delete_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $stream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'bar']),
        ]);

        $this->chronicler->expects($this->once())->method('delete')->with($stream->name());

        $this->tracker->watch(EventableChronicler::DELETE_STREAM_EVENT,
            function (StreamStory $story) use ($stream): void {
                $this->assertEquals(EventableChronicler::DELETE_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $stream->name());
                $story->withRaisedException(StreamNotFound::withStreamName($stream->name()));
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->delete($stream->name());
    }

    #[Test]
    public function it_retrieve_all_stream(): void
    {
        $stream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'bar']),
        ]);

        $args = [$stream->name(), V4AggregateId::create(), 'asc'];

        $this->chronicler->expects($this->once())
            ->method('retrieveAll')
            ->with(...$args)
            ->will($this->returnCallback(function () use ($stream) {
                yield from $stream->events();
            }));

        $this->tracker->watch(EventableChronicler::ALL_STREAM_EVENT,
            function (StreamStory $story) use ($args): void {
                $this->assertEquals(EventableChronicler::ALL_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $args);
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $streamEvents = $eventStore->retrieveAll(...$args);

        $this->assertEquals($stream->events()->current(), $streamEvents->current());
    }

    #[Test]
    public function it_raise_stream_not_found_on_retrieve_all_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $stream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'bar']),
        ]);

        $args = [$stream->name(), V4AggregateId::create(), 'asc'];

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

    #[Test]
    public function it_retrieve_filtered_stream(): void
    {
        $stream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'bar']),
        ]);

        $queryFilter = $this->createMock(QueryFilter::class);
        $args = [$stream->name(), $queryFilter];

        $this->chronicler->expects($this->once())
            ->method('retrieveFiltered')
            ->with(...$args)
            ->will($this->returnCallback(function () use ($stream) {
                yield from $stream->events();
            }));

        $this->tracker->watch(EventableChronicler::FILTERED_STREAM_EVENT,
            function (StreamStory $story) use ($args): void {
                $this->assertEquals(EventableChronicler::FILTERED_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($story->promise(), $args);
            });

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $streamEvents = $eventStore->retrieveFiltered(...$args);

        $this->assertEquals($stream->events()->current(), $streamEvents->current());
    }

    #[Test]
    public function it_raise_stream_not_found_exception_on_retrieve_filtered_stream(): void
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

    #[Test]
    public function it_filtered_stream_names(): void
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

    #[Test]
    public function it_filtered_category_names(): void
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
    #[Test]
    public function it_check_stream_exists(bool $streamExists): void
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

    #[Test]
    public function it_return_inner_event_store(): void
    {
        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $this->assertSame($this->chronicler, $eventStore->innerChronicler());
    }

    #[Test]
    public function it_return_event_stream_provider(): void
    {
        $eventStreamProvider = $this->createMock(EventStreamProvider::class);

        $this->chronicler
            ->expects($this->once())
            ->method('getEventStreamProvider')
            ->willReturn($eventStreamProvider);

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $this->assertSame($eventStreamProvider, $eventStore->getEventStreamProvider());
    }

    #[Test]
    public function it_subscribe_to_event_store(): void
    {
        $stream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'bar']),
        ]);

        $alteredStream = new Stream(new StreamName('foo'), [
            SomeEvent::fromContent(['foo' => 'baz']),
        ]);

        $this->chronicler->expects($this->once())->method('firstCommit')->with($alteredStream);

        $eventStore = new EventChronicler($this->chronicler, $this->tracker);

        $eventStore->subscribe(EventableChronicler::FIRST_COMMIT_EVENT,
            function (StreamStory $story) use ($alteredStream, $stream): void {
                $this->assertEquals(EventableChronicler::FIRST_COMMIT_EVENT, $story->currentEvent());

                $this->assertEquals($story->promise(), $stream);

                $story->deferred(fn () => $alteredStream);
            }, 100);

        $eventStore->firstCommit($stream);
    }

    #[Test]
    public function it_unsubscribe_to_event_store(): void
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
