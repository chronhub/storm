<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Chronicler\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\InMemory\AbstractInMemoryChronicler;
use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Chronicler\InMemory\StandaloneInMemoryChronicler;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\ChroniclerDecorator;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\DetermineStreamCategory;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_map;
use function array_reverse;
use function count;
use function iterator_to_array;
use function random_bytes;
use function range;

#[CoversClass(StandaloneInMemoryChronicler::class)]
#[CoversClass(AbstractInMemoryChronicler::class)]
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

    public function testInstance(): void
    {
        $this->assertFalse($this->chronicler->hasStream($this->streamName));
        $this->assertEmpty($this->chronicler->getStreams());
        $this->assertNotInstanceOf(ChroniclerDecorator::class, $this->chronicler);
    }

    public function testFirstCommit(): void
    {
        $stream = new Stream($this->streamName);

        $this->chronicler->firstCommit($stream);

        $this->assertTrue($this->chronicler->hasStream($this->streamName));
    }

    public function testStreamAlreadyExistsRaisedOnFirstCommit(): void
    {
        $this->expectException(StreamAlreadyExists::class);

        $stream = new Stream($this->streamName);

        $this->chronicler->firstCommit($stream);
        $this->chronicler->firstCommit($stream);
    }

    public function testPersistEventsOnFirstCommit(): void
    {
        $events = iterator_to_array($this->providePastEvent($this->aggregateId, 10));

        $stream = new Stream($this->streamName, $events);

        $this->chronicler->firstCommit($stream);

        $this->assertEquals(['operation' => $events], $this->chronicler->getStreams()->toArray());
        $this->assertCount(10, $this->chronicler->getStreams()->toArray()['operation']);
    }

    public function testDecorateStreamEventsWithInternalPositionHeader(): void
    {
        $headers = [
            EventHeader::AGGREGATE_VERSION => 12,
            EventHeader::AGGREGATE_ID => $this->aggregateId->toString(),
        ];

        $event = SomeEvent::fromContent(['foo' => 'bar'])->withHeaders($headers);
        $stream = new Stream($this->streamName, [$event]);

        $this->chronicler->firstCommit($stream);

        $pastEvent = $this->chronicler->getStreams()->first()[0];

        $this->assertArrayHasKey(EventHeader::INTERNAL_POSITION, $pastEvent->headers());
        $this->assertEquals(12, $pastEvent->header(EventHeader::INTERNAL_POSITION));
    }

    public function testStreamNotFoundRaisedOnAmendStream(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->assertFalse($this->chronicler->hasStream($this->streamName));

        $stream = new Stream($this->streamName, []);

        $this->chronicler->amend($stream);
    }

    public function testDeleteStreamWithEvents(): void
    {
        $events = iterator_to_array($this->providePastEvent($this->aggregateId, 10));

        $stream = new Stream($this->streamName, []);

        $this->assertFalse($this->chronicler->hasStream($this->streamName));

        $this->chronicler->firstCommit($stream);

        $this->assertTrue($this->chronicler->hasStream($this->streamName));
        $this->assertEquals(['operation' => []], $this->chronicler->getStreams()->toArray());

        $this->chronicler->amend(new Stream($this->streamName, $events));
        $this->assertEquals(['operation' => $events], $this->chronicler->getStreams()->toArray());

        $this->chronicler->delete($this->streamName);

        $this->assertFalse($this->chronicler->hasStream($this->streamName));

        $this->assertTrue($this->chronicler->getStreams()->isEmpty());
    }

    public function testStreamNotFoundOnDelete(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->assertFalse($this->chronicler->hasStream($this->streamName));

        $this->chronicler->delete($this->streamName);
    }

    #[DataProvider('provideDirection')]
    public function testRetrieveAllStreamEventsWithSorting(string $sortDirection): void
    {
        $events = iterator_to_array($this->providePastEvent($this->aggregateId, 5));
        $stream = new Stream($this->streamName, $events);

        $this->chronicler->firstCommit($stream);

        $recordedEvents = $this->chronicler->retrieveAll($this->streamName, $this->aggregateId, $sortDirection);

        $allEvents = [];
        foreach ($recordedEvents as $recordedEvent) {
            $allEvents[] = $recordedEvent;
        }

        $this->assertEquals(5, $recordedEvents->getReturn());
        $this->assertCount(5, $allEvents);

        $range = range(1, 5);

        if ($sortDirection === 'desc') {
            $range = array_reverse($range);
        }

        $this->assertEquals(
            $range,
            array_map(fn (DomainEvent $event): int => $event->header(EventHeader::INTERNAL_POSITION), $allEvents)
        );
    }

    public function testRetrieveAllStreamEventsByAggregateId(): void
    {
        $headers = [
            EventHeader::INTERNAL_POSITION => $currentVersion = 5,
            EventHeader::AGGREGATE_VERSION => $currentVersion,
            EventHeader::AGGREGATE_ID => $this->aggregateId,
        ];

        $event = SomeEvent::fromContent(['foo' => 'bar'])->withHeaders($headers);

        $stream = new Stream($this->streamName, [$event]);

        $this->chronicler->firstCommit($stream);

        $recordedEvents = $this->chronicler->retrieveAll($this->streamName, $this->aggregateId);
        $recordedEvent = $recordedEvents->current();
        $recordedEvents->next();

        $this->assertEquals(1, $recordedEvents->getReturn());
        $this->assertEquals($event, $recordedEvent);
    }

    #[DataProvider('provideInMemoryFilter')]
    public function testRetrieveFiltered(InMemoryQueryFilter $filter, array $range): void
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

        $this->assertEquals(
            $range,
            array_map(fn (DomainEvent $event): int => $event->header(EventHeader::INTERNAL_POSITION), $allEvents)
        );
    }

    public function testExceptionRaisedWhenQueryFilterIsNotInstanceOfInMemoryQueryFilter(): void
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

    public function testFilterSteamNames(): void
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

    public function testFilterCategoryNames(): void
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

    public function testStreamNotFoundRaisedWhenRetrieveAllStreamEvents(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->assertFalse($this->chronicler->hasStream($this->streamName));

        $this->chronicler->retrieveAll($this->streamName, $this->aggregateId)->current();
    }

    public function testNoStreamEventReturnRaisedWhenRetrieveAllStreamEvents(): void
    {
        $this->chronicler->firstCommit(new Stream($this->streamName));

        try {
            $this->chronicler->retrieveAll($this->streamName, $this->aggregateId)->current();
        } catch (StreamNotFound $e) {
            $this->assertInstanceOf(NoStreamEventReturn::class, $e);
            $this->assertInstanceOf(StreamNotFound::class, $e);
        }
    }

    public function testGetEventStreamProvider(): void
    {
        $this->assertInstanceOf(InMemoryEventStream::class, $this->chronicler->getEventStreamProvider());
    }

    public static function provideDirection(): Generator
    {
        yield ['asc'];
        yield ['desc'];
    }

    public static function provideInMemoryFilter(): Generator
    {
        yield [
            new class() implements InMemoryQueryFilter
            {
                public function apply(): callable
                {
                    return fn (DomainEvent $event): bool => (int) $event->header(EventHeader::INTERNAL_POSITION) >= 5;
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
                    return function (DomainEvent $event): bool {
                        $internalPosition = (int) $event->header(EventHeader::INTERNAL_POSITION);

                        return $internalPosition >= 3 && $internalPosition <= 5;
                    };
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
                    return function (DomainEvent $event): bool {
                        $internalPosition = (int) $event->header(EventHeader::INTERNAL_POSITION);

                        return $internalPosition >= 1 && $internalPosition <= 4;
                    };
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

        while ($limit !== 0) {
            $headers = [
                EventHeader::INTERNAL_POSITION => $currentVersion = ++$version,
                EventHeader::AGGREGATE_VERSION => $currentVersion,
                EventHeader::AGGREGATE_ID => $aggregateId->toString(),
                EventHeader::AGGREGATE_ID_TYPE => $aggregateId::class,
            ];

            yield SomeEvent::fromContent(['password' => random_bytes(16)])->withHeaders($headers);

            $limit--;
        }
    }
}
