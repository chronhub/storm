<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Generator;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Stream\DetermineStreamCategory;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Chronicler\InMemory\StandaloneInMemoryChronicler;
use function count;
use function range;
use function array_map;
use function random_bytes;
use function array_reverse;
use function iterator_to_array;

final class StandaloneInMemoryChroniclerTest extends UnitTestCase
{
    private StandaloneInMemoryChronicler $chronicler;

    private StreamName $streamName;

    private AggregateIdentity|V4AggregateId $aggregateId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->streamName = new StreamName('operation');
        $this->aggregateId = V4AggregateId::create();

        $this->chronicler = new StandaloneInMemoryChronicler(
            new InMemoryEventStream(),
            new DetermineStreamCategory()
        );
    }

    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $this->assertFalse($this->chronicler->hasStream($this->streamName));
        $this->assertEmpty($this->chronicler->streams());
    }

    /**
     * @test
     */
    public function it_persist_first_commit(): void
    {
        $stream = new Stream($this->streamName);

        $this->chronicler->firstCommit($stream);

        $this->assertTrue($this->chronicler->hasStream($this->streamName));
    }

    /**
     * @test
     */
    public function it_raises_exception_when_stream_name_already_exists(): void
    {
        $this->expectException(StreamAlreadyExists::class);

        $stream = new Stream($this->streamName);

        $this->chronicler->firstCommit($stream);
        $this->chronicler->firstCommit($stream);
    }

    /**
     * @test
     */
    public function it_persist_events_on_first_commit(): void
    {
        $events = iterator_to_array($this->providePastEvent($this->aggregateId, 10));

        $stream = new Stream($this->streamName, $events);

        $this->chronicler->firstCommit($stream);

        $this->assertEquals(['operation' => $events], $this->chronicler->streams()->toArray());
        $this->assertCount(10, $this->chronicler->streams()->toArray()['operation']);
    }

    /**
     * @test
     */
    public function it_persist_events_on_first_commit_with_one_stream_strategy(): void
    {
        $events = iterator_to_array($this->providePastEvent($this->aggregateId, 10));

        $stream = new Stream($this->streamName, $events);

        $this->chronicler->firstCommit($stream);

        $this->assertEquals(['operation' => $events], $this->chronicler->streams()->toArray());
    }

    /**
     * @test
     */
    public function it_decorate_internal_position_header_with_aggregate_version(): void
    {
        $headers = [
            EventHeader::AGGREGATE_VERSION => 12,
            EventHeader::AGGREGATE_ID => $this->aggregateId->toString(),
        ];

        $event = SomeEvent::fromContent(['password' => random_bytes(16)])->withHeaders($headers);
        $stream = new Stream($this->streamName, [$event]);

        $this->chronicler->firstCommit($stream);

        $pastEvent = $this->chronicler->streams()->first()[0];

        $this->assertArrayHasKey(EventHeader::INTERNAL_POSITION, $pastEvent->headers());
        $this->assertEquals(12, $pastEvent->header(EventHeader::INTERNAL_POSITION));
    }

    /**
     * @test
     */
    public function it_raise_stream_not_found_exception_when_persisting_events_with_unknown_stream_name(): void
    {
        $this->expectException(StreamNotFound::class);

        $stream = new Stream($this->streamName, []);

        $this->chronicler->amend($stream);
    }

    /**
     * @test
     */
    public function it_delete_stream_with_events(): void
    {
        $events = iterator_to_array($this->providePastEvent($this->aggregateId, 10));

        $stream = new Stream($this->streamName, []);

        $this->assertFalse($this->chronicler->hasStream($this->streamName));

        $this->chronicler->firstCommit($stream);

        $this->assertTrue($this->chronicler->hasStream($this->streamName));
        $this->assertEquals(['operation' => []], $this->chronicler->streams()->toArray());

        $this->chronicler->amend(new Stream($this->streamName, $events));
        $this->assertEquals(['operation' => $events], $this->chronicler->streams()->toArray());

        $this->chronicler->delete($this->streamName);

        $this->assertFalse($this->chronicler->hasStream($this->streamName));

        $this->assertTrue($this->chronicler->streams()->isEmpty());
    }

    /**
     * @test
     */
    public function it_raise_stream_not_found_exception_when_deleting_unknown_stream_name(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->chronicler->delete($this->streamName);
    }

    /**
     * @test
     *
     * @dataProvider provideDirection
     */
    public function it_retrieve_all_stream_events_with_direction(string $direction): void
    {
        $events = iterator_to_array($this->providePastEvent($this->aggregateId, 5));
        $stream = new Stream($this->streamName, $events);

        $this->chronicler->firstCommit($stream);

        $recordedEvents = $this->chronicler->retrieveAll($this->streamName, $this->aggregateId, $direction);

        $allEvents = [];
        foreach ($recordedEvents as $recordedEvent) {
            $allEvents[] = $recordedEvent;
        }

        $this->assertEquals(5, $recordedEvents->getReturn());
        $this->assertCount(5, $allEvents);

        $range = range(1, 5);

        if ('desc' === $direction) {
            $range = array_reverse($range);
        }

        $this->assertEquals(
            $range,
            array_map(fn (DomainEvent $event): int => $event->header(EventHeader::INTERNAL_POSITION), $allEvents)
        );
    }

    /**
     * @test
     */
    public function it_retrieve_all_events_from_aggregate_id_instance_in_header(): void
    {
        $headers = [
            EventHeader::INTERNAL_POSITION => $currentVersion = 5,
            EventHeader::AGGREGATE_VERSION => $currentVersion,
            EventHeader::AGGREGATE_ID => $this->aggregateId,
        ];

        $event = SomeEvent::fromContent(['password' => random_bytes(16)])->withHeaders($headers);

        $stream = new Stream($this->streamName, [$event]);

        $this->chronicler->firstCommit($stream);

        $recordedEvents = $this->chronicler->retrieveAll($this->streamName, $this->aggregateId);
        $recordedEvent = $recordedEvents->current();
        $recordedEvents->next();

        $this->assertEquals(1, $recordedEvents->getReturn());
        $this->assertEquals($event, $recordedEvent);
    }

    /**
     * @test
     *
     * @dataProvider provideInMemoryFilter
     */
    public function it_retrieve_filtered_stream_events(InMemoryQueryFilter $filter, array $range): void
    {
        $events = iterator_to_array($this->providePastEvent($this->aggregateId, 10));
        $stream = new Stream($this->streamName, $events);

        $this->chronicler->firstCommit($stream);

        $recordedEvents = $this->chronicler->retrieveFiltered($this->streamName, $filter);

        $allEvents = [];
        foreach ($recordedEvents as $recordedEvent) {
            $allEvents[] = $recordedEvent;
        }

        $this->assertEquals(count($range), $recordedEvents->getReturn());
        $this->assertCount(count($range), $allEvents);

        $this->assertEquals($range, array_map(fn (DomainEvent $event): int => $event->header(EventHeader::INTERNAL_POSITION), $allEvents));
    }

    /**
     * @test
     */
    public function it_raise_exception_if_query_filter_is_not_an_instance_of_in_memory_query_filter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Query filter must be an instance of '.InMemoryQueryFilter::class);

        $streamName = $this->streamName;

        $this->chronicler->retrieveFiltered($streamName, new class() implements QueryFilter
        {
            public function apply(): callable
            {
                return fn (): string => 'nope';
            }
        });
    }

    /**
     * @test
     */
    public function it_fetch_stream_names(): void
    {
        $balanceStream = new StreamName('balance');
        $orderStream = new StreamName('order');

        $this->assertFalse($this->chronicler->hasStream($balanceStream));
        $this->assertFalse($this->chronicler->hasStream($orderStream));

        $this->chronicler->firstCommit(new Stream($balanceStream));
        $this->chronicler->firstCommit(new Stream($orderStream));

        $this->assertTrue($this->chronicler->hasStream($balanceStream));
        $this->assertTrue($this->chronicler->hasStream($orderStream));

        $wanted = [$balanceStream, $orderStream];
        $this->assertEquals(['balance', 'order'], $this->chronicler->filterStreamNames(...$wanted));

        $wanted = [$orderStream, new StreamName('does_not_exist')];
        $this->assertEquals(['order'], $this->chronicler->filterStreamNames(...$wanted));

        $wanted = [$balanceStream, $orderStream, new StreamName('does_not_exist')];
        $this->assertEquals(['balance', 'order'], $this->chronicler->filterStreamNames(...$wanted));
    }

    /**
     * @test
     */
    public function it_fetch_categories(): void
    {
        $balanceAddStream = new StreamName('balance-add');
        $balanceSubtractStream = new StreamName('balance-subtract');
        $someStream = new StreamName('some_stream');

        $this->assertFalse($this->chronicler->hasStream($this->streamName));
        $this->assertFalse($this->chronicler->hasStream($balanceAddStream));
        $this->assertFalse($this->chronicler->hasStream($balanceSubtractStream));
        $this->assertFalse($this->chronicler->hasStream($someStream));

        $this->chronicler->firstCommit(new Stream($balanceAddStream));
        $this->chronicler->firstCommit(new Stream($balanceSubtractStream));
        $this->chronicler->firstCommit(new Stream($someStream));

        $this->assertTrue($this->chronicler->hasStream($balanceAddStream));
        $this->assertTrue($this->chronicler->hasStream($balanceSubtractStream));
        $this->assertTrue($this->chronicler->hasStream($someStream));

        $this->assertEquals(
            ['balance-add', 'balance-subtract'],
            $this->chronicler->filterCategoryNames('balance')
        );
    }

    /**
     * @test
     */
    public function it_raise_stream_not_found_exception_when_stream_does_not_exist(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->assertFalse($this->chronicler->hasStream($this->streamName));

        $this->chronicler->retrieveAll($this->streamName, $this->aggregateId)->current();
    }

    /**
     * @test
     */
    public function it_raise_stream_not_found_exception_when_stream_exists_but_stream_events_are_empty(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->chronicler->firstCommit(new Stream($this->streamName));

        $this->chronicler->retrieveAll($this->streamName, $this->aggregateId)->current();
    }

    /**
     * @test
     */
    public function it_access_event_stream_provider(): void
    {
        $this->assertInstanceOf(InMemoryEventStream::class, $this->chronicler->getEventStreamProvider());
    }

    public function provideDirection(): Generator
    {
        yield ['asc'];
        yield ['desc'];
    }

    public function provideInMemoryFilter(): Generator
    {
        yield [
            new class() implements InMemoryQueryFilter
            {
                public function apply(): callable
                {
                    return fn (DomainEvent $event): bool => $event->header(EventHeader::INTERNAL_POSITION) >= 5;
                }

                public function orderBy(): string
                {
                    return 'asc';
                }
            }, range(5, 10),
        ];

        yield [
            new class() implements InMemoryQueryFilter
            {
                public function apply(): callable
                {
                    return fn (DomainEvent $event): bool => $event->header(EventHeader::INTERNAL_POSITION) >= 3
                        && $event->header(EventHeader::INTERNAL_POSITION) <= 5;
                }

                public function orderBy(): string
                {
                    return 'asc';
                }
            }, range(3, 5),
        ];

        yield [
            new class() implements InMemoryQueryFilter
            {
                public function apply(): callable
                {
                    return fn (DomainEvent $event): bool => $event->header(EventHeader::INTERNAL_POSITION) >= 1
                        && $event->header(EventHeader::INTERNAL_POSITION) <= 4;
                }

                public function orderBy(): string
                {
                    return 'desc';
                }
            }, array_reverse(range(1, 4)),
        ];
    }

    private function providePastEvent(AggregateIdentity $aggregateId, int $limit): Generator
    {
        $version = 0;

        while (0 !== $limit) {
            $headers = [
                EventHeader::INTERNAL_POSITION => $currentVersion = ++$version,
                EventHeader::AGGREGATE_VERSION => $currentVersion,
                EventHeader::AGGREGATE_ID => $aggregateId->toString(),
            ];

            yield SomeEvent::fromContent(['password' => random_bytes(16)])->withHeaders($headers);

            $limit--;
        }
    }
}
