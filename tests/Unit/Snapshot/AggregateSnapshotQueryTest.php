<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Snapshot;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Aggregate\AggregateQueryRepository;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Snapshot\SnapshotQueryScope;
use Chronhub\Storm\Contracts\Snapshot\SnapshotStore;
use Chronhub\Storm\Snapshot\AggregateSnapshotQuery;
use Chronhub\Storm\Snapshot\Snapshot;
use Chronhub\Storm\Tests\Stubs\AggregateRootWithSnapshottingStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class AggregateSnapshotQueryTest extends UnitTestCase
{
    private AggregateType|MockObject $aggregateType;

    private SnapshotStore|MockObject $snapshotStore;

    private SnapshotQueryScope|MockObject $snapshotQueryScope;

    private AggregateQueryRepository|MockObject $aggregateQuery;

    protected function setUp(): void
    {
        $this->aggregateType = $this->createMock(AggregateType::class);
        $this->snapshotStore = $this->createMock(SnapshotStore::class);
        $this->snapshotQueryScope = $this->createMock(SnapshotQueryScope::class);
        $this->aggregateQuery = $this->createMock(AggregateQueryRepository::class);
    }

    public function testRetrieveFromSnapshotAndReturnUpdatedAggregate(): void
    {
        $snapshotFilter = $this->createMock(QueryFilter::class);
        $aggregateId = V4AggregateId::create();
        $aggregate = AggregateRootWithSnapshottingStub::create($aggregateId, SomeEvent::fromContent(['foo' => 'bar']));
        $aggregate->releaseEvents();

        $this->assertSame(1, $aggregate->version());

        $snapshot = new Snapshot(
            $aggregate::class,
            $aggregateId->toString(),
            $aggregate,
            1,
            (new PointInTime())->now()
        );

        $this->snapshotStore
            ->expects($this->once())
            ->method('get')
            ->with($aggregate::class, $aggregateId->toString())
            ->willReturn($snapshot);

        $this->snapshotQueryScope
            ->expects($this->once())
            ->method('matchAggregateGreaterThanVersion')
            ->with($aggregateId, $aggregate::class, 1)
            ->willReturn($snapshotFilter);

        $this->aggregateType
            ->expects($this->once())
            ->method('current')
            ->willReturn($aggregate::class);

        $this->aggregateQuery
            ->expects($this->once())
            ->method('retrieveHistory')
            ->with($aggregateId, $snapshotFilter)
            ->willReturnCallback(function () {
                yield SomeEvent::fromContent(['foo' => 'bar']);

                return 1;
            });

        $aggregateSnapshotQuery = $this->newAggregateSnapshotQuery();

        $aggregateRoot = $aggregateSnapshotQuery->retrieve($aggregateId);

        $this->assertSame(2, $aggregateRoot->version());
    }

    public function testRetrieveWhenNoSnapshot(): void
    {
        $aggregateId = V4AggregateId::create();
        $aggregate = AggregateRootWithSnapshottingStub::create($aggregateId, SomeEvent::fromContent(['foo' => 'bar']));
        $aggregate->releaseEvents();

        $this->assertSame(1, $aggregate->version());

        $this->snapshotStore
            ->expects($this->once())
            ->method('get')
            ->with($aggregate::class, $aggregateId->toString())
            ->willReturn(null);

        $this->snapshotQueryScope->expects($this->never())->method('matchAggregateGreaterThanVersion');
        $this->aggregateQuery->expects($this->never())->method('retrieveHistory');

        $this->aggregateType
            ->expects($this->once())
            ->method('current')
            ->willReturn($aggregate::class);

        $this->aggregateQuery
            ->expects($this->once())
            ->method('retrieve')
            ->with($aggregateId)
            ->willReturn($aggregate);

        $this->assertSame($aggregate, $this->newAggregateSnapshotQuery()->retrieve($aggregateId));
    }

    public function testRetrieveFiltered(): void
    {
        $aggregateId = V4AggregateId::create();
        $aggregate = AggregateRootWithSnapshottingStub::create($aggregateId, SomeEvent::fromContent(['foo' => 'bar']));
        $aggregate->releaseEvents();

        $queryFilter = $this->createMock(QueryFilter::class);

        $this->aggregateQuery
            ->expects($this->once())
            ->method('retrieveFiltered')
            ->with($aggregateId, $queryFilter)
            ->willReturn($aggregate);

        $this->assertSame($aggregate, $this->newAggregateSnapshotQuery()->retrieveFiltered($aggregateId, $queryFilter));
    }

    public function testRetrieveFromSnapshotAndReturnAggregateFromLastSnapshot(): void
    {
        $snapshotFilter = $this->createMock(QueryFilter::class);
        $aggregateId = V4AggregateId::create();
        $aggregate = AggregateRootWithSnapshottingStub::create($aggregateId, SomeEvent::fromContent(['foo' => 'bar']));
        $aggregate->releaseEvents();

        $this->assertSame(1, $aggregate->version());

        $snapshot = new Snapshot(
            $aggregate::class,
            $aggregateId->toString(),
            $aggregate,
            1,
            (new PointInTime())->now()
        );

        $this->snapshotStore
            ->expects($this->once())
            ->method('get')
            ->with($aggregate::class, $aggregateId->toString())
            ->willReturn($snapshot);

        $this->snapshotQueryScope
            ->expects($this->once())
            ->method('matchAggregateGreaterThanVersion')
            ->with($aggregateId, $aggregate::class, 1)
            ->willReturn($snapshotFilter);

        $this->aggregateType
            ->expects($this->once())
            ->method('current')
            ->willReturn($aggregate::class);

        $this->aggregateQuery
            ->expects($this->once())
            ->method('retrieveHistory')
            ->with($aggregateId, $snapshotFilter)
            ->willReturnCallback(function () {
                throw new NoStreamEventReturn('no event');
            });

        $aggregateSnapshotQuery = $this->newAggregateSnapshotQuery();

        $aggregateRoot = $aggregateSnapshotQuery->retrieve($aggregateId);

        $this->assertSame($aggregate, $aggregateRoot);
    }

    private function newAggregateSnapshotQuery(): AggregateSnapshotQuery
    {
        return new AggregateSnapshotQuery(
            $this->aggregateType,
            $this->snapshotStore,
            $this->snapshotQueryScope,
            $this->aggregateQuery
        );
    }
}
