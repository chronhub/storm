<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Generator;
use Throwable;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Chronicler\TrackStream;
use PHPUnit\Framework\MockObject\Exception;
use Chronhub\Storm\Chronicler\ProvideEvents;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Chronicler\EventChronicler;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Contracts\Tracker\StreamStory;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\ConcurrencyException;
use function count;
use function iterator_to_array;

#[CoversClass(ProvideEvents::class)]
final class ProvideEventsTest extends UnitTestCase
{
    private Chronicler|MockObject $chronicler;

    private AggregateIdentity|MockObject $aggregateId;

    private Stream $stream;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->stream = new Stream(new StreamName('account'), []);
        $this->chronicler = $this->createMock(Chronicler::class);
        $this->aggregateId = $this->createMock(AggregateIdentity::class);
    }

    #[Test]
    public function it_dispatch_first_commit_event(): void
    {
        $this->chronicler->expects($this->once())
            ->method('firstCommit')
            ->with($this->stream);

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FIRST_COMMIT_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::FIRST_COMMIT_EVENT, $story->currentEvent());
                $this->assertEquals($this->stream, $story->promise());
            }
        );

        $eventChronicler->firstCommit($this->stream);
    }

    #[Test]
    public function it_raise_stream_already_exits_on_first_commit(): void
    {
        $this->expectException(StreamAlreadyExists::class);

        $exception = StreamAlreadyExists::withStreamName(new StreamName('foo'));

        $this->chronicler->expects($this->once())
            ->method('firstCommit')
            ->with($this->stream)
            ->willThrowException($exception);

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FIRST_COMMIT_EVENT,
            function (StreamStory $story) use ($exception): void {
                $this->assertEquals(EventableChronicler::FIRST_COMMIT_EVENT, $story->currentEvent());
                $this->assertEquals($exception, $story->exception());
                $this->assertTrue($story->hasStreamAlreadyExits());
            }
        );

        $eventChronicler->firstCommit($this->stream);
    }

    #[Test]
    public function it_dispatch_persist_stream_event(): void
    {
        $this->chronicler->expects($this->once())
            ->method('amend')
            ->with($this->stream);

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::PERSIST_STREAM_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::PERSIST_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($this->stream, $story->promise());
            }
        );

        $eventChronicler->amend($this->stream);
    }

    #[DataProvider('provideExceptionOnPersistStreamEvents')]
    #[Test]
    public function it_raise_exception_on_persist_stream_events(Throwable $exception): void
    {
        $this->expectException($exception::class);

        $this->chronicler->expects($this->once())
            ->method('amend')
            ->with($this->stream)
            ->willThrowException($exception);

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::PERSIST_STREAM_EVENT,
            function (StreamStory $story) use ($exception): void {
                $this->assertEquals(EventableChronicler::PERSIST_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($exception, $story->exception());
            }
        );

        $eventChronicler->amend($this->stream);
    }

    #[Test]
    public function it_dispatch_delete_stream_event(): void
    {
        $this->chronicler->expects($this->once())
            ->method('delete')
            ->with($this->stream->name());

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::DELETE_STREAM_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::DELETE_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($this->stream->name(), $story->promise());
            }
        );

        $eventChronicler->delete($this->stream->name());
    }

    #[DataProvider('provideEventWithDirection')]
    #[Test]
    public function it_dispatch_retrieve_all_events_with_direction(string $eventName, string $direction): void
    {
        $expectedEvents = [
            new Message(SomeEvent::fromContent(['foo' => 'bar'])),
            new Message(SomeEvent::fromContent(['bar' => 'foo'])),
        ];

        $this->chronicler
            ->expects($this->once())
            ->method('retrieveAll')
            ->with($this->stream->name(), $this->aggregateId, $direction)
            ->will($this->returnCallback(function () use ($expectedEvents): Generator {
                yield from $expectedEvents;

                return count($expectedEvents);
            }));

        $eventChronicler = $this->eventChroniclerInstance(
            $eventName,
            function (StreamStory $story) use ($eventName): void {
                $this->assertEquals($eventName, $story->currentEvent());
            }
        );

        $messages = $eventChronicler->retrieveAll($this->stream->name(), $this->aggregateId, $direction);

        $messages = iterator_to_array($messages);

        $this->assertEquals($expectedEvents, $messages);
    }

    #[DataProvider('provideEventWithDirection')]
    #[Test]
    public function it_raise_stream_not_found_exception_on_retrieve_all_events(string $eventName, string $direction): void
    {
        $this->expectException(StreamNotFound::class);

        $exception = StreamNotFound::withStreamName($this->stream->name());

        $this->chronicler
            ->expects($this->once())
            ->method('retrieveAll')
            ->with($this->stream->name(), $this->aggregateId, $direction)
            ->willThrowException($exception);

        $eventChronicler = $this->eventChroniclerInstance(
            $eventName,
            function (StreamStory $story) use ($eventName, $direction): void {
                $this->assertEquals($eventName, $story->currentEvent());
                $this->assertTrue($story->hasStreamNotFound());

                $this->assertEquals([$this->stream->name(), $this->aggregateId, $direction], $story->promise());
            }
        );

        $eventChronicler->retrieveAll($this->stream->name(), $this->aggregateId, $direction)->current();
    }

    /**
     * ^@test
     */
    public function it_raise_stream_not_found_exception_on_delete_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $exception = StreamNotFound::withStreamName($this->stream->name());

        $this->chronicler->expects($this->once())
            ->method('delete')
            ->with($this->stream->name())
            ->willThrowException($exception);

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::DELETE_STREAM_EVENT,
            function (StreamStory $story) use ($exception): void {
                $this->assertEquals(EventableChronicler::DELETE_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($exception, $story->exception());
                $this->assertTrue($story->hasStreamNotFound());
            }
        );

        $eventChronicler->delete($this->stream->name());
    }

    #[Test]
    public function it_dispatch_retrieve_events_with_query_filter(): void
    {
        $queryFilter = $this->createMock(QueryFilter::class);

        $expectedEvents = [
            new Message(SomeEvent::fromContent(['foo' => 'bar'])),
            new Message(SomeEvent::fromContent(['bar' => 'foo'])),
        ];

        $this->chronicler
            ->expects($this->once())
            ->method('retrieveFiltered')
            ->with($this->stream->name(), $queryFilter)
            ->will($this->returnCallback(function () use ($expectedEvents): Generator {
                yield from $expectedEvents;

                return count($expectedEvents);
            }));

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FILTERED_STREAM_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::FILTERED_STREAM_EVENT, $story->currentEvent());

                $this->assertEquals($this->stream->name(), $story->promise()->name());
            },
        );

        $messages = $eventChronicler->retrieveFiltered($this->stream->name(), $queryFilter);

        $messages = iterator_to_array($messages);

        $this->assertEquals($expectedEvents, $messages);
    }

    #[Test]
    public function it_raise_stream_not_found_exception_on_retrieve_events_with_query_filter(): void
    {
        $this->expectException(StreamNotFound::class);

        $exception = StreamNotFound::withStreamName($this->stream->name());

        $queryFilter = $this->createMock(QueryFilter::class);

        $this->chronicler
            ->expects($this->once())
            ->method('retrieveFiltered')
            ->with($this->stream->name(), $queryFilter)
            ->willThrowException($exception);

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FILTERED_STREAM_EVENT,
            function (StreamStory $story) use ($exception): void {
                $this->assertEquals(EventableChronicler::FILTERED_STREAM_EVENT, $story->currentEvent());

                $this->assertEquals($exception, $story->exception());
            }
        );

        $eventChronicler->retrieveFiltered($this->stream->name(), $queryFilter)->current();
    }

    #[DataProvider('provideBool')]
    #[Test]
    public function it_dispatch_has_stream_event(bool $streamExists): void
    {
        $this->chronicler->expects($this->once())
            ->method('hasStream')
            ->with($this->stream->name())
            ->willReturn($streamExists);

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::HAS_STREAM_EVENT,
            function (StreamStory $story) use ($streamExists): void {
                $this->assertEquals(EventableChronicler::HAS_STREAM_EVENT, $story->currentEvent());

                $this->assertEquals($streamExists, $story->promise());
            }
        );

        $eventChronicler->hasStream($this->stream->name());
    }

    #[Test]
    public function it_dispatch_fetch_stream_names_and_return_stream_names_if_exists(): void
    {
        $fooStreamName = new StreamName('foo');
        $barStreamName = new StreamName('bar');

        $this->chronicler
            ->expects($this->once())
            ->method('filterStreamNames')
            ->with($fooStreamName, $barStreamName)
            ->willReturn([$fooStreamName]);

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FILTER_STREAM_NAMES,
            function (StreamStory $story) use ($fooStreamName): void {
                $this->assertEquals(EventableChronicler::FILTER_STREAM_NAMES, $story->currentEvent());

                $this->assertEquals([$fooStreamName], $story->promise());
            }
        );

        $this->assertEquals([$fooStreamName], $eventChronicler->filterStreamNames($fooStreamName, $barStreamName));
    }

    #[Test]
    public function it_dispatch_fetch_category_names_and_return_category_names_if_exists(): void
    {
        $categories = ['user-123', 'user-124'];

        $this->chronicler
            ->expects($this->once())
            ->method('filterCategoryNames')
            ->with('user-123', 'user-124')
            ->willReturn($categories);

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FILTER_CATEGORY_NAMES,
            function (StreamStory $story) use ($categories): void {
                $this->assertEquals(EventableChronicler::FILTER_CATEGORY_NAMES, $story->currentEvent());

                $this->assertEquals($categories, $story->promise());
            }
        );

        $this->assertEquals($categories, $eventChronicler->filterCategoryNames('user-123', 'user-124'));
    }

    #[Test]
    public function it_unsubscribe_listener_from_tracker(): void
    {
        $this->chronicler
            ->expects($this->exactly(3))
            ->method('filterCategoryNames')
            ->with('nope')
            ->willReturn(['nope']);

        $tracker = new TrackStream();

        $eventChronicler = new EventChronicler($this->chronicler, $tracker);

        $count = 0;
        $listener = $eventChronicler->subscribe(
            EventableChronicler::FILTER_CATEGORY_NAMES,
            function () use (&$count): void {
                $count++;
            }, 2);

        $eventChronicler->filterCategoryNames('nope');
        $eventChronicler->filterCategoryNames('nope');

        $this->assertEquals(2, $count);

        $eventChronicler->unsubscribe($listener);

        $eventChronicler->filterCategoryNames('nope');
        $this->assertEquals(2, $count);
    }

    #[Test]
    public function it_access_inner_chronicler(): void
    {
        $tracker = new TrackStream();

        $eventChronicler = new EventChronicler($this->chronicler, $tracker);

        $this->assertEquals($this->chronicler, $eventChronicler->innerChronicler());
    }

    #[Test]
    public function it_access_event_stream_provider(): void
    {
        $tracker = new TrackStream();

        $eventStreamProvider = $this->createMock(EventStreamProvider::class);

        $this->chronicler
            ->expects($this->once())
            ->method('getEventStreamProvider')
            ->willReturn($eventStreamProvider);

        $eventChronicler = new EventChronicler($this->chronicler, $tracker);

        $this->assertSame($eventStreamProvider, $eventChronicler->getEventStreamProvider());
    }

    public static function provideExceptionOnPersistStreamEvents(): Generator
    {
        yield [StreamNotFound::withStreamName(new StreamName('some_stream'))];

        yield [new ConcurrencyException('failed')];
    }

    public static function provideEventWithDirection(): Generator
    {
        yield [EventableChronicler::ALL_STREAM_EVENT, 'asc'];

        yield [EventableChronicler::ALL_REVERSED_STREAM_EVENT, 'desc'];
    }

    public static function provideBool(): Generator
    {
        yield [true];

        yield [false];
    }

    private function eventChroniclerInstance(string $event, callable $assert): EventChronicler
    {
        $eventChronicler = new EventChronicler($this->chronicler, new TrackStream());

        $eventChronicler->subscribe($event, $assert);

        return $eventChronicler;
    }
}
