<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Generator;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Aggregate\V4AggregateId;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Aggregate\ReconstituteAggregate;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Tests\Stubs\ReconstituteAggregateRootStub;
use function count;
use function iterator_to_array;

#[CoversClass(ReconstituteAggregate::class)]
final class ReconstituteAggregateTest extends UnitTestCase
{
    private Chronicler|MockObject $chronicler;

    private StreamProducer|MockObject $streamProducer;

    private AggregateType|MockObject $aggregateType;

    private AggregateIdentity|MockObject $someIdentity;

    private StreamName $streamName;

    private string $identityString = '9ef864f7-43e2-48c8-9944-639a2d927a06';

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->chronicler = $this->createMock(Chronicler::class);
        $this->streamProducer = $this->createMock(StreamProducer::class);
        $this->aggregateType = $this->createMock(AggregateType::class);
        $this->someIdentity = V4AggregateId::fromString($this->identityString);
        $this->streamName = new StreamName('balance');
    }

    #[Test]
    public function it_reconstitute_aggregate_root_from_history(): void
    {
        $events = iterator_to_array($this->provideFourDummyEvents());
        $countEvents = count($events);

        $this->streamProducer->expects($this->once())
            ->method('toStreamName')
            ->with($this->someIdentity)
            ->willReturn($this->streamName);

        $this->aggregateType->expects($this->once())
            ->method('from')
            ->with($events[0])
            ->willReturn(AggregateRootStub::class);

        $this->chronicler->expects($this->once())
            ->method('retrieveAll')
            ->with($this->streamName, $this->someIdentity)
            ->will($this->returnCallback(function () use ($events, $countEvents) {
                yield from $events;

                return $countEvents;
            }));

        $stub = new ReconstituteAggregateRootStub($this->chronicler, $this->streamProducer, $this->aggregateType);

        $reconstituteAggregateRoot = $stub->reconstitute($this->someIdentity);

        $this->assertInstanceOf(AggregateRootStub::class, $reconstituteAggregateRoot);
        $this->assertEquals($countEvents, $reconstituteAggregateRoot->version());
        $this->assertEquals($countEvents, $reconstituteAggregateRoot->getAppliedEvents());
    }

    #[Test]
    public function it_reconstitute_aggregate_root_from_filtered_history(): void
    {
        $events = iterator_to_array($this->provideFourDummyEvents());

        $this->streamProducer->expects($this->once())
            ->method('toStreamName')
            ->with($this->someIdentity)
            ->willReturn($this->streamName);

        $this->aggregateType->expects($this->once())
            ->method('from')
            ->with($events[0])
            ->willReturn(AggregateRootStub::class);

        $queryFilter = $this->createMock(QueryFilter::class);

        $this->chronicler->expects($this->once())
            ->method('retrieveFiltered')
            ->with($this->streamName, $queryFilter)
            ->will($this->returnCallback(function () use ($events) {
                yield $events[0];
                yield $events[1];

                return 2;
            }));

        $stub = new ReconstituteAggregateRootStub($this->chronicler, $this->streamProducer, $this->aggregateType);

        $reconstituteAggregateRoot = $stub->reconstitute($this->someIdentity, $queryFilter);

        $this->assertInstanceOf(AggregateRootStub::class, $reconstituteAggregateRoot);
        $this->assertEquals(2, $reconstituteAggregateRoot->version());
        $this->assertEquals(2, $reconstituteAggregateRoot->getAppliedEvents());
    }

    #[Test]
    public function it_return_null_aggregate_root_from_empty_history(): void
    {
        $this->streamProducer->expects($this->once())
            ->method('toStreamName')
            ->with($this->someIdentity)
            ->willReturn($this->streamName);

        $this->aggregateType->expects($this->never())->method('from');

        $this->chronicler->expects($this->once())
            ->method('retrieveAll')
            ->with($this->streamName, $this->someIdentity)
            ->will($this->returnCallback(function () {
                yield from [];

                return 0;
            }));

        $stub = new ReconstituteAggregateRootStub($this->chronicler, $this->streamProducer, $this->aggregateType);

        $reconstituteAggregateRoot = $stub->reconstitute($this->someIdentity);

        $this->assertNull($reconstituteAggregateRoot);
    }

    #[Test]
    public function it_return_null_aggregate_root_when_stream_not_found_exception_is_raised(): void
    {
        $this->streamProducer->expects($this->once())
            ->method('toStreamName')
            ->with($this->someIdentity)
            ->willReturn($this->streamName);

        $this->aggregateType->expects($this->never())->method('from');

        $this->chronicler->expects($this->once())
            ->method('retrieveAll')
            ->with($this->streamName, $this->someIdentity)
            ->will($this->throwException(StreamNotFound::withStreamName($this->streamName)));

        $stub = new ReconstituteAggregateRootStub($this->chronicler, $this->streamProducer, $this->aggregateType);

        $reconstituteAggregateRoot = $stub->reconstitute($this->someIdentity);

        $this->assertNull($reconstituteAggregateRoot);
    }

    private function provideFourDummyEvents(): Generator
    {
        yield from [
            SomeEvent::fromContent(['count' => 1]),
            SomeEvent::fromContent(['count' => 2]),
            SomeEvent::fromContent(['count' => 3]),
            SomeEvent::fromContent(['count' => 4]),
        ];

        return 4;
    }
}
