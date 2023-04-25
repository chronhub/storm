<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Snapshot;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateSnapshotQueryRepository;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Snapshot\SnapshotQueryScope;
use Chronhub\Storm\Snapshot\RestoreAggregateSnapshot;
use Chronhub\Storm\Snapshot\Snapshot;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\AggregateRootWithSnapshottingStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

#[CoversClass(RestoreAggregateSnapshot::class)]
final class RestoreAggregateSnapshotTest extends UnitTestCase
{
    private AggregateSnapshotQueryRepository|MockObject $queryRepository;

    private SnapshotQueryScope|MockObject $snapshotQueryScope;

    private QueryFilter|MockObject $queryFilter;

    private AggregateIdentity $aggregateId;

    private StreamName $streamName;

    protected function setUp(): void
    {
        $this->queryRepository = $this->createMock(AggregateSnapshotQueryRepository::class);
        $this->snapshotQueryScope = $this->createMock(SnapshotQueryScope::class);
        $this->queryFilter = $this->createMock(QueryFilter::class);
        $this->aggregateId = V4AggregateId::create();
        $this->streamName = new StreamName('customer');
    }

    public function testRestoreAggregateFromScratch(): void
    {
        $this->snapshotQueryScope
            ->expects($this->once())
            ->method('matchAggregateBetweenIncludedVersion')
            ->with($this->aggregateId, 1, PHP_INT_MAX)
            ->willReturn($this->queryFilter);

        $this->queryRepository
            ->expects($this->once())
            ->method('retrieveHistory')
            ->with($this->aggregateId, $this->queryFilter)
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

        $this->queryRepository
            ->expects($this->once())
            ->method('retrieveHistory')
            ->with($this->aggregateId, $this->queryFilter)
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

        $this->queryRepository
            ->expects($this->once())
            ->method('retrieveHistory')
            ->with($this->aggregateId, $this->queryFilter)
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

        $this->queryRepository
            ->expects($this->once())
            ->method('retrieveHistory')
            ->with($this->aggregateId, $this->queryFilter)
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

    private function newInstance(): RestoreAggregateSnapshot
    {
        return new RestoreAggregateSnapshot(
            $this->queryRepository,
            $this->snapshotQueryScope
        );
    }
}
