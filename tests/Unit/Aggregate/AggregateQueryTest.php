<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Chronhub\Storm\Aggregate\AggregateQuery;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(AggregateQuery::class)]
final class AggregateQueryTest extends UnitTestCase
{
    private Chronicler|MockObject $chronicler;

    private StreamProducer|MockObject $streamProducer;

    private AggregateType|MockObject $aggregateType;

    private V4AggregateId $aggregateId;

    private StreamName $streamName;

    protected function setUp(): void
    {
        $this->chronicler = $this->createMock(Chronicler::class);
        $this->streamProducer = $this->createMock(StreamProducer::class);
        $this->aggregateType = $this->createMock(AggregateType::class);
        $this->aggregateId = V4AggregateId::create();
        $this->streamName = new StreamName('account');
    }

    public function testRetrieve(): void
    {
        $this->streamProducer
            ->expects($this->once())
            ->method('toStreamName')
            ->with($this->aggregateId)
            ->willReturn($this->streamName);

        $this->chronicler
            ->expects($this->once())
            ->method('retrieveAll')
            ->with($this->streamName, $this->aggregateId)
            ->willReturn($this->provideFourEvents());

        $this->aggregateType
            ->expects($this->once())
            ->method('from')
            ->with($this->isInstanceOf(SomeEvent::class))
            ->willReturn(AggregateRootStub::class);

        $query = $this->newAggregateQuery();

        $aggregate = $query->retrieve($this->aggregateId);

        $this->assertInstanceOf(AggregateRootStub::class, $aggregate);
        $this->assertSame(4, $aggregate->version());
        $this->assertSame($this->aggregateId, $aggregate->aggregateId());
    }

    public function testRetrieveFiltered(): void
    {
        $queryFilter = $this->createMock(QueryFilter::class);

        $this->streamProducer
            ->expects($this->once())
            ->method('toStreamName')
            ->with($this->aggregateId)
            ->willReturn($this->streamName);

        $this->chronicler
            ->expects($this->once())
            ->method('retrieveFiltered')
            ->with($this->streamName, $queryFilter)
            ->willReturn($this->provideFourEvents());

        $this->aggregateType
            ->expects($this->once())
            ->method('from')
            ->with($this->isInstanceOf(SomeEvent::class))
            ->willReturn(AggregateRootStub::class);

        $query = $this->newAggregateQuery();
        $aggregate = $query->retrieveFiltered($this->aggregateId, $queryFilter);

        $this->assertInstanceOf(AggregateRootStub::class, $aggregate);
        $this->assertSame(4, $aggregate->version());
        $this->assertSame($this->aggregateId, $aggregate->aggregateId());
    }

    public function testReturnNullAggregateWithNoEvents(): void
    {
        $this->streamProducer
            ->expects($this->once())
            ->method('toStreamName')
            ->with($this->aggregateId)
            ->willReturn($this->streamName);

        $this->chronicler
            ->expects($this->once())
            ->method('retrieveAll')
            ->with($this->streamName, $this->aggregateId)
            ->willReturn($this->provideNoEvents());

        $this->aggregateType->expects($this->never())->method('from');

        $query = $this->newAggregateQuery();
        $aggregate = $query->retrieve($this->aggregateId);

        $this->assertNull($aggregate);
    }

    #[DataProvider('provideStreamNotFoundException')]
    public function testReturnNullAggregateWhenStreamNotFoundRaised(StreamNotFound $streamNotFound): void
    {
        $this->streamProducer
            ->expects($this->once())
            ->method('toStreamName')
            ->with($this->aggregateId)
            ->willReturn($this->streamName);

        $this->chronicler
            ->expects($this->once())
            ->method('retrieveAll')
            ->with($this->streamName, $this->aggregateId)
            ->willReturnCallback(fn () => throw $streamNotFound);

        $this->aggregateType->expects($this->never())->method('from');

        $query = $this->newAggregateQuery();
        $aggregate = $query->retrieve($this->aggregateId);

        $this->assertNull($aggregate);
    }

    public static function provideStreamNotFoundException(): Generator
    {
        yield [StreamNotFound::withStreamName(new StreamName('account'))];
        yield [NoStreamEventReturn::withStreamName(new StreamName('account'))];
    }

    private function provideFourEvents(): Generator
    {
        yield SomeEvent::fromContent(['foo' => 'bar']);
        yield SomeEvent::fromContent(['foo' => 'bar']);
        yield SomeEvent::fromContent(['foo' => 'bar']);
        yield SomeEvent::fromContent(['foo' => 'bar']);

        return 4;
    }

    private function provideNoEvents(): Generator
    {
        yield from [];

        return 0;
    }

    private function newAggregateQuery(): AggregateQuery
    {
        return new AggregateQuery($this->chronicler, $this->streamProducer, $this->aggregateType);
    }
}
