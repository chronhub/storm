<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Snapshot;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateRepositoryWithSnapshotting;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Chronicler\ReadOnlyChronicler;
use Chronhub\Storm\Contracts\Snapshot\SnapshotQueryScope;
use Chronhub\Storm\Snapshot\RestoreAggregate;
use Chronhub\Storm\Snapshot\Snapshot;
use Chronhub\Storm\Stream\SingleStreamPerAggregate;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\AggregateRootWithSnapshottingStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

#[CoversClass(RestoreAggregate::class)]
final class RestoreAggregateTest extends UnitTestCase
{
    private ReadOnlyChronicler|MockObject $eventStore;

    private AggregateRepositoryWithSnapshotting|MockObject $aggregateRepository;

    private SnapshotQueryScope|MockObject $snapshotQueryScope;

    private QueryFilter|MockObject $queryFilter;

    private AggregateIdentity $aggregateId;

    private StreamName $streamName;

    private SingleStreamPerAggregate $streamProducer;

    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(ReadOnlyChronicler::class);
        $this->aggregateRepository = $this->createMock(AggregateRepositoryWithSnapshotting::class);
        $this->snapshotQueryScope = $this->createMock(SnapshotQueryScope::class);
        $this->queryFilter = $this->createMock(QueryFilter::class);
        $this->aggregateId = V4AggregateId::create();
        $this->streamName = new StreamName('customer');
        $this->streamProducer = new SingleStreamPerAggregate($this->streamName);
    }

    public function testRestoreAggregateFromScratch(): void
    {
        $this->snapshotQueryScope
            ->expects($this->once())
            ->method('matchAggregateBetweenIncludedVersion')
            ->with($this->aggregateId, 1, PHP_INT_MAX)
            ->willReturn($this->queryFilter);

        $this->aggregateRepository
            ->expects($this->once())
            ->method('getStreamProducer')
            ->willReturn($this->streamProducer);

        $this->aggregateRepository
            ->expects($this->once())
            ->method('getEventStore')
            ->willReturn($this->eventStore);

        $this->eventStore
            ->expects($this->once())
            ->method('retrieveFiltered')
            ->with($this->streamName->toString(), $this->queryFilter)
            ->willReturnCallback(static function () {
                yield SomeEvent::fromContent(['foo' => 'bar']);

                return 1;
            });

        $aggregate = $this->newInstance()->fromScratch($this->aggregateId, AggregateRootWithSnapshottingStub::class);

        $this->assertInstanceOf(AggregateRootWithSnapshottingStub::class, $aggregate);
        $this->assertEquals(1, $aggregate->version());
    }

    public function testExceptionRaisedWhenNoEventReturn(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Unable to take first snapshot for aggregate '.AggregateRootWithSnapshottingStub::class.' and id '.$this->aggregateId->toString()
        );

        $this->snapshotQueryScope
            ->expects($this->once())
            ->method('matchAggregateBetweenIncludedVersion')
            ->with($this->aggregateId, 1, PHP_INT_MAX)
            ->willReturn($this->queryFilter);

        $this->aggregateRepository
            ->expects($this->once())
            ->method('getStreamProducer')
            ->willReturn($this->streamProducer);

        $this->aggregateRepository
            ->expects($this->once())
            ->method('getEventStore')
            ->willReturn($this->eventStore);

        $this->eventStore
            ->expects($this->once())
            ->method('retrieveFiltered')
            ->with($this->streamName->toString(), $this->queryFilter)
            ->willReturnCallback(function (): void {
                throw StreamNotFound::withStreamName($this->streamName);
            });

        $this->newInstance()->fromScratch($this->aggregateId, AggregateRootWithSnapshottingStub::class);
    }

    public function testRestoreFromSnapshot(): void
    {
        $eventVersion = 1;
        $snapshot = $this->newSnapshot();

        $this->snapshotQueryScope
            ->expects($this->once())
            ->method('matchAggregateBetweenIncludedVersion')
            ->with($this->aggregateId, 2, $eventVersion + 1)
            ->willReturn($this->queryFilter);

        $this->aggregateRepository
            ->expects($this->once())
            ->method('getStreamProducer')
            ->willReturn($this->streamProducer);

        $this->aggregateRepository
            ->expects($this->once())
            ->method('getEventStore')
            ->willReturn($this->eventStore);

        $this->eventStore
            ->expects($this->once())
            ->method('retrieveFiltered')
            ->with($this->streamName->toString(), $this->queryFilter)
            ->willReturnCallback(static function () {
                yield SomeEvent::fromContent(['foo' => 'bar']);

                return 1;
            });

        $aggregate = $this->newInstance()->fromSnapshot($snapshot, $eventVersion);

        $this->assertInstanceOf(AggregateRootWithSnapshottingStub::class, $aggregate);
        $this->assertEquals(2, $aggregate->version());
    }

    public function testReturnNullFromSnapshotWhenNoStreamEventReturn(): void
    {
        $eventVersion = 1;
        $snapshot = $this->newSnapshot();

        $this->snapshotQueryScope
            ->expects($this->once())
            ->method('matchAggregateBetweenIncludedVersion')
            ->with($this->aggregateId, 2, $eventVersion + 1)
            ->willReturn($this->queryFilter);

        $this->aggregateRepository
            ->expects($this->once())
            ->method('getStreamProducer')
            ->willReturn($this->streamProducer);

        $this->aggregateRepository
            ->expects($this->once())
            ->method('getEventStore')
            ->willReturn($this->eventStore);

        $this->eventStore
            ->expects($this->once())
            ->method('retrieveFiltered')
            ->with($this->streamName->toString(), $this->queryFilter)
            ->willReturnCallback(function () {
                throw StreamNotFound::withStreamName($this->streamName);
            });

        $aggregate = $this->newInstance()->fromSnapshot($snapshot, $eventVersion);

        $this->assertNull($aggregate);
    }

    private function newSnapshot(): Snapshot
    {
        $clock = new PointInTime();
        $aggregate = AggregateRootWithSnapshottingStub::create($this->aggregateId);
        $aggregate->recordSomeEvents(SomeEvent::fromContent(['foo' => 'bar'])); //fixMe static
        $aggregate->releaseEvents();

        return new Snapshot(
            AggregateRootWithSnapshottingStub::class,
            $this->aggregateId->toString(),
            $aggregate,
            $aggregate->version(),
            $clock->now(),
        );
    }

    private function newInstance(): RestoreAggregate
    {
        return new RestoreAggregate(
            $this->aggregateRepository,
            $this->snapshotQueryScope
        );
    }
}
