<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Generator;
use Throwable;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Stream\StreamName;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Chronicler\TrackStream;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Chronicler\EventChronicler;
use Chronhub\Storm\Contracts\Tracker\StreamStory;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\ConcurrencyException;
use function iterator_to_array;

final class ProvideEventsTest extends ProphecyTestCase
{
    private Chronicler|ObjectProphecy $chronicler;

    private Stream $stream;

    private AggregateIdentity $aggregateId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stream = new Stream(new StreamName('account'), []);
        $this->chronicler = $this->prophesize(Chronicler::class);
        $this->aggregateId = $this->prophesize(AggregateIdentity::class)->reveal();
    }

    /**
     * @test
     */
    public function it_dispatch_first_commit_event(): void
    {
        $this->chronicler->firstCommit($this->stream)->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FIRST_COMMIT_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::FIRST_COMMIT_EVENT, $story->currentEvent());
                $this->assertEquals($this->stream, $story->promise());
            }
        );

        $eventChronicler->firstCommit($this->stream);
    }

    /**
     * @test
     */
    public function it_raise_stream_already_exits_on_first_commit(): void
    {
        $this->expectException(StreamAlreadyExists::class);

        $exception = StreamAlreadyExists::withStreamName(new StreamName('foo'));

        $this->chronicler->firstCommit($this->stream)->willThrow($exception)->shouldBeCalled();

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

    /**
     * @test
     */
    public function it_dispatch_persist_stream_event(): void
    {
        $this->chronicler->amend($this->stream)->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::PERSIST_STREAM_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::PERSIST_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($this->stream, $story->promise());
            }
        );

        $eventChronicler->amend($this->stream);
    }

    /**
     * @test
     *
     * @dataProvider provideExceptionOnPersistStreamEvents
     */
    public function it_raise_exception_on_persist_stream_events(Throwable $exception): void
    {
        $this->expectException($exception::class);

        $this->chronicler->amend($this->stream)->willThrow($exception)->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::PERSIST_STREAM_EVENT,
            function (StreamStory $story) use ($exception): void {
                $this->assertEquals(EventableChronicler::PERSIST_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($exception, $story->exception());
            }
        );

        $eventChronicler->amend($this->stream);
    }

    /**
     * @test
     */
    public function it_dispatch_delete_stream_event(): void
    {
        $this->chronicler->delete($this->stream->name())->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::DELETE_STREAM_EVENT,
            function (StreamStory $story): void {
                $this->assertEquals(EventableChronicler::DELETE_STREAM_EVENT, $story->currentEvent());
                $this->assertEquals($this->stream->name(), $story->promise());
            }
        );

        $eventChronicler->delete($this->stream->name());
    }

    /**
     * @test
     *
     * @dataProvider provideEventWithDirection
     */
    public function it_dispatch_retrieve_all_events_with_direction(string $eventName, string $direction): void
    {
        $expectedEvents = [
            new Message(SomeEvent::fromContent(['foo' => 'bar'])),
            new Message(SomeEvent::fromContent(['bar' => 'foo'])),
        ];

        $this->chronicler
            ->retrieveAll($this->stream->name(), $this->aggregateId, $direction)
            ->willYield($expectedEvents)
            ->shouldBeCalled();

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

    /**
     * @test
     *
     * @dataProvider provideEventWithDirection
     */
    public function it_raise_stream_not_found_exception_on_retrieve_all_events(string $eventName, string $direction): void
    {
        $this->expectException(StreamNotFound::class);

        $exception = StreamNotFound::withStreamName($this->stream->name());

        $this->chronicler
            ->retrieveAll($this->stream->name(), $this->aggregateId, $direction)
            ->willThrow($exception)
            ->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            $eventName,
            function (StreamStory $story) use ($eventName, $direction): void {
                $this->assertEquals($eventName, $story->currentEvent());
                $this->assertTrue($story->hasStreamNotFound());

                $this->assertEquals([
                    $this->stream->name(), $this->aggregateId, $direction,
                ], $story->promise());
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

        $this->chronicler->delete($this->stream->name())->willThrow($exception)->shouldBeCalled();

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

    /**
     * @test
     */
    public function it_dispatch_retrieve_events_with_query_filter(): void
    {
        $queryFilter = $this->prophesize(QueryFilter::class)->reveal();

        $expectedEvents = [
            new Message(SomeEvent::fromContent(['foo' => 'bar'])),
            new Message(SomeEvent::fromContent(['bar' => 'foo'])),
        ];

        $this->chronicler
            ->retrieveFiltered($this->stream->name(), $queryFilter)
            ->willYield($expectedEvents)
            ->shouldBeCalled();

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

    /**
     * @test
     */
    public function it_raise_stream_not_found_exception_on_retrieve_events_with_query_filter(): void
    {
        $this->expectException(StreamNotFound::class);

        $exception = StreamNotFound::withStreamName($this->stream->name());

        $queryFilter = $this->prophesize(QueryFilter::class)->reveal();

        $this->chronicler
            ->retrieveFiltered($this->stream->name(), $queryFilter)
            ->willThrow($exception)
            ->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FILTERED_STREAM_EVENT,
            function (StreamStory $story) use ($exception): void {
                $this->assertEquals(EventableChronicler::FILTERED_STREAM_EVENT, $story->currentEvent());

                $this->assertEquals($exception, $story->exception());
            }
        );

        $eventChronicler->retrieveFiltered($this->stream->name(), $queryFilter)->current();
    }

    /**
     * @test
     *
     * @dataProvider provideBool
     */
    public function it_dispatch_has_stream_event(bool $streamExists): void
    {
        $this->chronicler->hasStream($this->stream->name())->willReturn($streamExists)->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::HAS_STREAM_EVENT,
            function (StreamStory $story) use ($streamExists): void {
                $this->assertEquals(EventableChronicler::HAS_STREAM_EVENT, $story->currentEvent());

                $this->assertEquals($streamExists, $story->promise());
            }
        );

        $eventChronicler->hasStream($this->stream->name());
    }

    /**
     * @test
     */
    public function it_dispatch_fetch_stream_names_and_return_stream_names_if_exists(): void
    {
        $fooStreamName = new StreamName('foo');
        $barStreamName = new StreamName('bar');

        $this->chronicler
            ->filterStreamNames($fooStreamName, $barStreamName)
            ->willReturn([$fooStreamName])
            ->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FILTER_STREAM_NAMES,
            function (StreamStory $story) use ($fooStreamName): void {
                $this->assertEquals(EventableChronicler::FILTER_STREAM_NAMES, $story->currentEvent());

                $this->assertEquals([$fooStreamName], $story->promise());
            }
        );

        $this->assertEquals([$fooStreamName], $eventChronicler->filterStreamNames($fooStreamName, $barStreamName));
    }

    /**
     * @test
     */
    public function it_dispatch_fetch_category_names_and_return_category_names_if_exists(): void
    {
        $categories = ['user-123', 'user-124'];

        $this->chronicler
            ->filterCategoryNames('user-123', 'user-124')
            ->willReturn($categories)
            ->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FILTER_CATEGORY_NAMES,
            function (StreamStory $story) use ($categories): void {
                $this->assertEquals(EventableChronicler::FILTER_CATEGORY_NAMES, $story->currentEvent());

                $this->assertEquals($categories, $story->promise());
            }
        );

        $this->assertEquals($categories, $eventChronicler->filterCategoryNames('user-123', 'user-124'));
    }

    /**
     * @test
     */
    public function it_unsubscribe_listener_from_tracker(): void
    {
        $this->chronicler
            ->filterCategoryNames('nope')
            ->willReturn(['nope'])
            ->shouldBeCalledTimes(3);

        $tracker = new TrackStream();

        $eventChronicler = new EventChronicler($this->chronicler->reveal(), $tracker);

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

    /**
     * @test
     */
    public function it_access_inner_chronicler(): void
    {
        $tracker = new TrackStream();

        $eventChronicler = new EventChronicler($this->chronicler->reveal(), $tracker);

        $this->assertEquals($this->chronicler->reveal(), $eventChronicler->innerChronicler());
    }

    /**
     * @test
     */
    public function it_access_event_stream_provider(): void
    {
        $tracker = new TrackStream();

        $eventStreamProvider = $this->prophesize(EventStreamProvider::class)->reveal();

        $this->chronicler->getEventStreamProvider()->willReturn($eventStreamProvider)->shouldBeCalledOnce();

        $eventChronicler = new EventChronicler($this->chronicler->reveal(), $tracker);

        $this->assertSame($eventStreamProvider, $eventChronicler->getEventStreamProvider());
    }

    public function provideExceptionOnPersistStreamEvents(): Generator
    {
        yield [StreamNotFound::withStreamName(new StreamName('some_stream'))];

        yield [new ConcurrencyException('failed')];
    }

    public function provideEventWithDirection(): Generator
    {
        yield [EventableChronicler::ALL_STREAM_EVENT, 'asc'];

        yield [EventableChronicler::ALL_REVERSED_STREAM_EVENT, 'desc'];
    }

    public function provideBool(): Generator
    {
        yield [true];

        yield [false];
    }

    private function eventChroniclerInstance(string $event, callable $assert): EventChronicler
    {
        $eventChronicler = new EventChronicler(
            $this->chronicler->reveal(),
            new TrackStream(),
        );

        $eventChronicler->subscribe($event, $assert);

        return $eventChronicler;
    }
}
