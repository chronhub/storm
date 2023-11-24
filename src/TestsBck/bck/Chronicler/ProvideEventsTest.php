<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Chronicler\EventChronicler;
use Chronhub\Storm\Chronicler\Exceptions\ConcurrencyException;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\ProvideEvents;
use Chronhub\Storm\Chronicler\TrackStream;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Tracker\StreamStory;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Throwable;
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
        $this->stream = new Stream(new StreamName('account'), []);
        $this->chronicler = $this->createMock(Chronicler::class);
        $this->aggregateId = $this->createMock(AggregateIdentity::class);
    }

    public function testDispatchFirstCommitEvent(): void
    {
        $this->chronicler
            ->expects($this->once())
            ->method('firstCommit')
            ->with($this->stream);

        $eventChronicler = $this->newEventChronicler(
            EventableChronicler::FIRST_COMMIT_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::FIRST_COMMIT_EVENT, $story->currentEvent());
                $this->assertEquals($this->stream, $story->promise());
            }
        );

        $eventChronicler->firstCommit($this->stream);
    }

    public function testStreamAlreadyExistRaisedWhenDispatchFirstCommit(): void
    {
        $this->expectException(StreamAlreadyExists::class);

        $exception = StreamAlreadyExists::withStreamName(new StreamName('foo'));

        $this->chronicler
            ->expects($this->once())
            ->method('firstCommit')
            ->with($this->stream)
            ->willThrowException($exception);

        $eventChronicler = $this->newEventChronicler(
            EventableChronicler::FIRST_COMMIT_EVENT,
            function (StreamStory $story) use ($exception): void {
                $this->assertEquals(EventableChronicler::FIRST_COMMIT_EVENT, $story->currentEvent());
                $this->assertEquals($exception, $story->exception());
                $this->assertTrue($story->hasStreamAlreadyExits());
            }
        );

        $eventChronicler->firstCommit($this->stream);
    }

    public function testDispatchPersistEvent(): void
    {
        $this->chronicler
            ->expects($this->once())
            ->method('amend')
            ->with($this->stream);

        $eventChronicler = $this->newEventChronicler(
            EventableChronicler::PERSIST_STREAM_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::PERSIST_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($this->stream, $story->promise());
            }
        );

        $eventChronicler->amend($this->stream);
    }

    #[DataProvider('provideExceptionOnPersistStreamEvents')]
    public function testExceptionRaisedWhenDispatchPersistEvent(Throwable $exception): void
    {
        $this->expectException($exception::class);

        $this->chronicler
            ->expects($this->once())
            ->method('amend')
            ->with($this->stream)
            ->willThrowException($exception);

        $eventChronicler = $this->newEventChronicler(
            EventableChronicler::PERSIST_STREAM_EVENT,
            function (StreamStory $story) use ($exception): void {
                $this->assertEquals(EventableChronicler::PERSIST_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($exception, $story->exception());
            }
        );

        $eventChronicler->amend($this->stream);
    }

    public function testDispatchDeleteEvent(): void
    {
        $this->chronicler
            ->expects($this->once())
            ->method('delete')
            ->with($this->stream->name());

        $eventChronicler = $this->newEventChronicler(
            EventableChronicler::DELETE_STREAM_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::DELETE_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($this->stream->name(), $story->promise());
            }
        );

        $eventChronicler->delete($this->stream->name());
    }

    #[DataProvider('provideEventWithDirection')]
    public function testDispatchRetrieveAllWithSorting(string $eventName, string $direction): void
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

        $eventChronicler = $this->newEventChronicler(
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
    public function testStreamNotFoundRaisedWhenDispatchRetrieveAllEvent(string $eventName, string $direction): void
    {
        $this->expectException(StreamNotFound::class);

        $exception = StreamNotFound::withStreamName($this->stream->name());

        $this->chronicler
            ->expects($this->once())
            ->method('retrieveAll')
            ->with($this->stream->name(), $this->aggregateId, $direction)
            ->willThrowException($exception);

        $eventChronicler = $this->newEventChronicler(
            $eventName,
            function (StreamStory $story) use ($eventName, $direction): void {
                $this->assertEquals($eventName, $story->currentEvent());
                $this->assertTrue($story->hasStreamNotFound());

                $this->assertEquals([$this->stream->name(), $this->aggregateId, $direction], $story->promise());
            }
        );

        $eventChronicler->retrieveAll($this->stream->name(), $this->aggregateId, $direction)->current();
    }

    public function testStreamNotFoundRaisedWhenDispatchDeleteEvent(): void
    {
        $this->expectException(StreamNotFound::class);

        $exception = StreamNotFound::withStreamName($this->stream->name());

        $this->chronicler
            ->expects($this->once())
            ->method('delete')
            ->with($this->stream->name())
            ->willThrowException($exception);

        $eventChronicler = $this->newEventChronicler(
            EventableChronicler::DELETE_STREAM_EVENT,
            function (StreamStory $story) use ($exception): void {
                $this->assertEquals(EventableChronicler::DELETE_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($exception, $story->exception());
                $this->assertTrue($story->hasStreamNotFound());
            }
        );

        $eventChronicler->delete($this->stream->name());
    }

    public function testDispatchRetrieveFilteredEvent(): void
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

        $eventChronicler = $this->newEventChronicler(
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

    public function testStreamNotFoundRaisedWhenDispatchRetrieveFilteredEvent(): void
    {
        $this->expectException(StreamNotFound::class);

        $exception = StreamNotFound::withStreamName($this->stream->name());

        $queryFilter = $this->createMock(QueryFilter::class);

        $this->chronicler
            ->expects($this->once())
            ->method('retrieveFiltered')
            ->with($this->stream->name(), $queryFilter)
            ->willThrowException($exception);

        $eventChronicler = $this->newEventChronicler(
            EventableChronicler::FILTERED_STREAM_EVENT,
            function (StreamStory $story) use ($exception): void {
                $this->assertEquals(EventableChronicler::FILTERED_STREAM_EVENT, $story->currentEvent());

                $this->assertEquals($exception, $story->exception());
            }
        );

        $eventChronicler->retrieveFiltered($this->stream->name(), $queryFilter)->current();
    }

    #[DataProvider('provideBool')]
    public function testDispatchHasStreamEvent(bool $streamExists): void
    {
        $this->chronicler
            ->expects($this->once())
            ->method('hasStream')
            ->with($this->stream->name())
            ->willReturn($streamExists);

        $eventChronicler = $this->newEventChronicler(
            EventableChronicler::HAS_STREAM_EVENT,
            function (StreamStory $story) use ($streamExists): void {
                $this->assertEquals(EventableChronicler::HAS_STREAM_EVENT, $story->currentEvent());

                $this->assertEquals($streamExists, $story->promise());
            }
        );

        $eventChronicler->hasStream($this->stream->name());
    }

    public function testDispatchFilterStreamNamesEvent(): void
    {
        $fooStreamName = new StreamName('foo');
        $barStreamName = new StreamName('bar');

        $this->chronicler
            ->expects($this->once())
            ->method('filterStreamNames')
            ->with($fooStreamName, $barStreamName)
            ->willReturn([$fooStreamName]);

        $eventChronicler = $this->newEventChronicler(
            EventableChronicler::FILTER_STREAM_NAMES,
            function (StreamStory $story) use ($fooStreamName): void {
                $this->assertEquals(EventableChronicler::FILTER_STREAM_NAMES, $story->currentEvent());

                $this->assertEquals([$fooStreamName], $story->promise());
            }
        );

        $this->assertEquals([$fooStreamName], $eventChronicler->filterStreamNames($fooStreamName, $barStreamName));
    }

    public function testDispatchFilterCategoryNamesEvent(): void
    {
        $categories = ['user-123', 'user-124'];

        $this->chronicler
            ->expects($this->once())
            ->method('filterCategoryNames')
            ->with('user-123', 'user-124')
            ->willReturn($categories);

        $eventChronicler = $this->newEventChronicler(
            EventableChronicler::FILTER_CATEGORY_NAMES,
            function (StreamStory $story) use ($categories): void {
                $this->assertEquals(EventableChronicler::FILTER_CATEGORY_NAMES, $story->currentEvent());

                $this->assertEquals($categories, $story->promise());
            }
        );

        $this->assertEquals($categories, $eventChronicler->filterCategoryNames('user-123', 'user-124'));
    }

    public function testUnsubscribeListeners(): void
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

    public function testGetInnerChronicler(): void
    {
        $tracker = new TrackStream();

        $eventChronicler = new EventChronicler($this->chronicler, $tracker);

        $this->assertEquals($this->chronicler, $eventChronicler->innerChronicler());
    }

    public function testGetEventStreamProvider(): void
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

    private function newEventChronicler(string $event, callable $assert): EventChronicler
    {
        $eventChronicler = new EventChronicler($this->chronicler, new TrackStream());

        $eventChronicler->subscribe($event, $assert);

        return $eventChronicler;
    }
}
