<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Snapshot;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Aggregate\AggregateRootWithSnapshotting;
use Chronhub\Storm\Snapshot\Snapshot;
use Chronhub\Storm\Tests\UnitTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Snapshot::class)]
final class SnapshotTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $now = (new PointInTime())->now();
        $aggregateId = V4AggregateId::create()->toString();
        $aggregateRoot = $this->createMock(AggregateRootWithSnapshotting::class);

        $snapshot = new Snapshot(
            AggregateRootWithSnapshotting::class,
            $aggregateId,
            $aggregateRoot,
            1,
            $now,
        );

        $this->assertSame(AggregateRootWithSnapshotting::class, $snapshot->aggregateType);
        $this->assertSame($aggregateId, $snapshot->aggregateId);
        $this->assertSame($aggregateRoot, $snapshot->aggregateRoot);
        $this->assertSame(1, $snapshot->lastVersion);
        $this->assertSame($now, $snapshot->createdAt);
    }

    public function testExceptionRaisedWhenVersionIsLessThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Aggregate version must be greater or equal than 1, current is 0');

        new Snapshot(
            AggregateRootWithSnapshotting::class,
            V4AggregateId::create()->toString(),
            $this->createMock(AggregateRootWithSnapshotting::class),
            0,
            (new PointInTime())->now(),
        );
    }
}
