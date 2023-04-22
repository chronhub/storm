<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Snapshot;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Snapshot\InMemorySnapshotStore;
use Chronhub\Storm\Snapshot\PersistEveryVersionSnapshotProvider;
use Chronhub\Storm\Snapshot\RestoreAggregate;
use Chronhub\Storm\Snapshot\Snapshot;
use Chronhub\Storm\Tests\Stubs\AggregateRootWithSnapshottingStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(PersistEveryVersionSnapshotProvider::class)]
final class PersistEveryVersionSnapshotProviderTest extends UnitTestCase
{
    private RestoreAggregate|MockObject $restoreAggregate;

    private InMemorySnapshotStore $snapshotStore;

    private AggregateIdentity $aggregateId;

    protected function setUp(): void
    {
        $this->restoreAggregate = $this->createMock(RestoreAggregate::class);
        $this->snapshotStore = new InMemorySnapshotStore();
        $this->aggregateId = V4AggregateId::fromString('de748856-e694-47ab-bb47-9c101bb95114');
    }

    #[DataProvider('provideEventLessThanTenVersion')]
    public function testDoesNotSnapshotTillEventVersionIsLessThanPersistEveryXVersion(SomeEvent $event): void
    {
        $this->assertNull($this->snapshotStore->get($this->aggregateId->toString(), AggregateRootWithSnapshottingStub::class));

        $this->restoreAggregate->expects($this->never())->method('fromScratch');
        $this->restoreAggregate->expects($this->never())->method('fromSnapshot');

        $snapshotProvider = $this->newSnapshotProvider(10);
        $snapshotProvider->store($event);

        $this->assertNull($this->snapshotStore->get($this->aggregateId->toString(), AggregateRootWithSnapshottingStub::class));
    }

    #[DataProvider('provideEventExceptFirstVersion')]
    public function testTakeFirstSnapshot(SomeEvent $event): void
    {
        $eventVersion = $event->header(EventHeader::AGGREGATE_VERSION);

        if ($eventVersion === 10) {
            $this->restoreAggregate
                ->expects($this->once())
                ->method('fromScratch')
                ->with($this->aggregateId, AggregateRootWithSnapshottingStub::class)
                ->willReturn($this->provideAggregateWithVersion(1));
        } else {
            $this->restoreAggregate->expects($this->never())->method('fromScratch');
        }

        $this->restoreAggregate->expects($this->never())->method('fromSnapshot');

        $snapshotProvider = $this->newSnapshotProvider(10);
        $snapshotProvider->store($event);

        $snapshot = $this->snapshotStore->get($this->aggregateId->toString(), AggregateRootWithSnapshottingStub::class);

        if ($eventVersion !== 11) {
            $this->assertNull($snapshot);
        } else {
            $this->assertInstanceOf(Snapshot::class, $snapshot);
            $this->assertSame($this->aggregateId->toString(), $snapshot->aggregateId);
            $this->assertSame(AggregateRootWithSnapshottingStub::class, $snapshot->aggregateType);
            $this->assertSame(11, $snapshot->lastVersion);
        }
    }

    public function testTakeSnapshotEveryTenEventsAndAssertNotRestored(): void
    {
        $snapshotProvider = $this->newSnapshotProvider(10);
        $events = $this->provideFiftyOneEventVersion();

        foreach ($events as $event) {
            $eventVersion = (int) $event->header(EventHeader::AGGREGATE_VERSION);

            if ($eventVersion === 11) {
                $this->restoreAggregate
                    ->expects($this->once())
                    ->method('fromScratch')
                    ->with($this->aggregateId, AggregateRootWithSnapshottingStub::class)
                    ->willReturn($this->provideAggregateWithVersion(1));
            }

            $snapshotProvider->store($event);
        }
    }

    public function testTakeSnapshotEveryTenEventsAndAssertRestoredFromScratch(): void
    {
        $snapshotProvider = $this->newSnapshotProvider(10);
        $events = $this->provideFiftyOneEventVersion();

        foreach ($events as $event) {
            $eventVersion = (int) $event->header(EventHeader::AGGREGATE_VERSION);

            if ($eventVersion === 11) {
                $this->restoreAggregate
                    ->expects($this->once())
                    ->method('fromScratch')
                    ->with($this->aggregateId, AggregateRootWithSnapshottingStub::class)
                    ->willReturn($this->provideAggregateWithVersion(1));
            }

            $snapshotProvider->store($event);
        }
    }

    public function testTakeSnapshotEveryTenEventsAndAssertRestoredFromSnapshot(): void
    {
        $this->markTestSkipped('not working yet');

        $events = $this->provideFiftyOneEventVersion();

        $this->snapshotStore->save(
            $this->provideSnapshot(11)
        );

        foreach ($events as $event) {
            $eventVersion = (int) $event->header(EventHeader::AGGREGATE_VERSION);

            $expectedVersion = $eventVersion - ($eventVersion % 10) + 1;

            if ($eventVersion !== 1 && $expectedVersion === $eventVersion) {
                //dump($expectedVersion);
//                $snapshot = $this->provideSnapshot($expectedVersion);
//                $this->snapshotStore->save($snapshot);

                  $this->restoreAggregate
                      ->expects($this->exactly(5))
                      ->method('fromSnapshot')
                      ->with($this->isInstanceOf(Snapshot::class), $expectedVersion)
                      ->willReturn($this->provideAggregateWithVersion($expectedVersion));
            }

            $snapshotProvider = $this->newSnapshotProvider(10);

            $snapshotProvider->store($event);
        }
    }

    public static function provideEventLessThanTenVersion(): Generator
    {
        $i = 1;
        while ($i !== 10) {
            yield [
                SomeEvent::fromContent(['foo' => 'bar'])
                    ->withHeaders([
                        EventHeader::AGGREGATE_ID => 'de748856-e694-47ab-bb47-9c101bb95114',
                        EventHeader::AGGREGATE_ID_TYPE => V4AggregateId::class,
                        EventHeader::AGGREGATE_VERSION => $i,
                        EventHeader::AGGREGATE_TYPE => AggregateRootWithSnapshottingStub::class,
                    ]),
            ];
            $i++;
        }
    }

    public static function provideEventExceptFirstVersion(): Generator
    {
        $i = 2;
        while ($i !== 11) {
            yield [
                SomeEvent::fromContent(['foo' => 'bar'])
                    ->withHeaders([
                        EventHeader::AGGREGATE_ID => 'de748856-e694-47ab-bb47-9c101bb95114',
                        EventHeader::AGGREGATE_ID_TYPE => V4AggregateId::class,
                        EventHeader::AGGREGATE_VERSION => $i,
                        EventHeader::AGGREGATE_TYPE => AggregateRootWithSnapshottingStub::class,
                    ]),
            ];
            $i++;
        }
    }

    private function provideFiftyOneEventVersion(): Generator
    {
        $i = 1;
        while ($i !== 52) {
            yield SomeEvent::fromContent(['foo' => 'bar'])
                ->withHeaders([
                    EventHeader::AGGREGATE_ID => 'de748856-e694-47ab-bb47-9c101bb95114',
                    EventHeader::AGGREGATE_ID_TYPE => V4AggregateId::class,
                    EventHeader::AGGREGATE_VERSION => $i,
                    EventHeader::AGGREGATE_TYPE => AggregateRootWithSnapshottingStub::class,
                ]);
            $i++;
        }
    }

    private function provideAggregateWithVersion(int $version): AggregateRootWithSnapshottingStub
    {
        $aggregate = AggregateRootWithSnapshottingStub::create($this->aggregateId);

        while ($aggregate->version() !== $version) {
            $aggregate->recordSomeEvents(SomeEvent::fromContent(['foo' => 'bar']));
        }

        $aggregate->releaseEvents();

        return $aggregate;
    }

    private function provideSnapshot(int $version): Snapshot
    {
        return new Snapshot(
            AggregateRootWithSnapshottingStub::class,
            $this->aggregateId->toString(),
            $this->provideAggregateWithVersion($version),
            $version,
            (new PointInTime())->now()
        );
    }

    private function newSnapshotProvider(int $everyVersion): PersistEveryVersionSnapshotProvider
    {
        return new PersistEveryVersionSnapshotProvider(
            $this->snapshotStore,
            $this->restoreAggregate,
            new PointInTime(),
            $everyVersion
        );
    }
}
