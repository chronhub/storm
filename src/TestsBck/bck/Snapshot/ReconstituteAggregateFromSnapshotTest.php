<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Snapshot;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Aggregate\AggregateRootWithSnapshotting;
use Chronhub\Storm\Snapshot\ReconstituteAggregateFromSnapshot;
use Chronhub\Storm\Tests\Stubs\AggregateRootWithSnapshottingStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(ReconstituteAggregateFromSnapshot::class)]
final class ReconstituteAggregateFromSnapshotTest extends UnitTestCase
{
    public function testReturnAggregateWithNoEvent(): void
    {
        $aggregateId = V4AggregateId::create();
        $stub = AggregateRootWithSnapshottingStub::create($aggregateId, SomeEvent::fromContent(['name' => 'steph']));
        $stub->releaseEvents();

        $this->assertSame(1, $stub->version());

        $aggregate = $stub->reconstituteFromSnapshotting($this->provideEmptyGenerator());

        $this->assertEquals($aggregate, $stub);
        $this->assertNotSame($aggregate, $stub);
    }

    public function testReconstituteAggregateWithEvents(): void
    {
        $aggregateId = V4AggregateId::create();
        $stub = AggregateRootWithSnapshottingStub::create($aggregateId, SomeEvent::fromContent(['name' => 'steph']));
        $stub->releaseEvents();

        $this->assertSame(1, $stub->version());

        $aggregate = $stub->reconstituteFromSnapshotting($this->provideFourEvents());

        $this->assertNotEquals($aggregate, $stub);
        $this->assertSame(5, $aggregate->version());
    }

    public function testExceptionRaisedWithInvalidInstance(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Aggregate must implement '.AggregateRootWithSnapshotting::class);

        $this->provideInvalidAggregate()->reconstituteFromSnapshotting($this->provideEmptyGenerator());
    }

    public function testExceptionRaisedWithZeroVersion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Aggregate version must be greater than zero');

        $aggregateId = V4AggregateId::create();

        $stub = AggregateRootWithSnapshottingStub::create($aggregateId);

        $this->assertSame(0, $stub->version());

        $stub->reconstituteFromSnapshotting($this->provideEmptyGenerator());
    }

    private function provideInvalidAggregate(): object
    {
        return new class
        {
            use ReconstituteAggregateFromSnapshot;

            protected function apply(object $event): void
            {
            }
        };
    }

    private function provideFourEvents(): Generator
    {
        yield SomeEvent::fromContent(['name' => 'steph']);
        yield SomeEvent::fromContent(['name' => 'steph']);
        yield SomeEvent::fromContent(['name' => 'steph']);
        yield SomeEvent::fromContent(['name' => 'steph']);
    }

    private function provideEmptyGenerator(): Generator
    {
        yield from [];
    }
}
