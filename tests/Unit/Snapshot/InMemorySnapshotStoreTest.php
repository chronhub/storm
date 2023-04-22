<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Snapshot;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Aggregate\AggregateRootWithSnapshotting;
use Chronhub\Storm\Contracts\Snapshot\SnapshotStore;
use Chronhub\Storm\Snapshot\InMemorySnapshotStore;
use Chronhub\Storm\Snapshot\Snapshot;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Tests\Stubs\AnotherAggregateRootStub;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InMemorySnapshotStore::class)]
final class InMemorySnapshotStoreTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $store = new InMemorySnapshotStore();

        $this->assertInstanceOf(SnapshotStore::class, $store);
        $this->assertNull($store->get(AggregateRootStub::class, 'bar'));
    }

    public function testSave(): void
    {
        $store = new InMemorySnapshotStore();

        $snapshots = $this->provideSnapshots();

        $store->save(...$snapshots);

        $this->assertSame($snapshots[0], $store->get(AggregateRootStub::class, 'foo'));
        $this->assertSame($snapshots[1], $store->get(AggregateRootStub::class, 'baz'));
        $this->assertSame($snapshots[2], $store->get(AnotherAggregateRootStub::class, 'bar'));
    }

    public function testDeleteByAggregateType(): void
    {
        $store = new InMemorySnapshotStore();

        $snapshots = $this->provideSnapshots();

        $store->save(...$snapshots);

        $this->assertSame($snapshots[0], $store->get(AggregateRootStub::class, 'foo'));
        $this->assertSame($snapshots[1], $store->get(AggregateRootStub::class, 'baz'));
        $this->assertSame($snapshots[2], $store->get(AnotherAggregateRootStub::class, 'bar'));

        $store->deleteByAggregateType(AggregateRootStub::class);

        $this->assertNull($store->get(AggregateRootStub::class, 'foo'));
        $this->assertNull($store->get(AggregateRootStub::class, 'baz'));
        $this->assertSame($snapshots[2], $store->get(AnotherAggregateRootStub::class, 'bar'));
    }

    /**
     * @return array<Snapshot>
     */
    private function provideSnapshots(): array
    {
        return [
            new Snapshot(
                AggregateRootStub::class,
                'foo',
                $this->createMock(AggregateRootWithSnapshotting::class),
                1,
                (new PointInTime())->now(),
            ),

            new Snapshot(
                AggregateRootStub::class,
                'baz',
                $this->createMock(AggregateRootWithSnapshotting::class),
                100,
                (new PointInTime())->now(),
            ),

            new Snapshot(
                AnotherAggregateRootStub::class,
                'bar',
                $this->createMock(AggregateRootWithSnapshotting::class),
                100,
                (new PointInTime())->now(),
            ),
        ];
    }
}
