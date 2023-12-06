<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Snapshot;

use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Snapshot\SnapshotProvider;
use Chronhub\Storm\Snapshot\SnapshotReadModel;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(SnapshotReadModel::class)]
class SnapshotReadModelTest extends UnitTestCase
{
    private SnapshotProvider|MockObject $snapshotProvider;

    protected function setUp(): void
    {
        $this->snapshotProvider = $this->createMock(SnapshotProvider::class);
    }

    public function testInstance(): void
    {
        $readModel = new SnapshotReadModel(
            $this->snapshotProvider,
            ['fqn_aggregate_type'],
        );

        $this->assertCount(0, $readModel->getSnapshots());
        $this->assertFalse($readModel->isInitialized());

        $readModel->initialize();

        $this->assertTrue($readModel->isInitialized());
    }

    public function testStackEvents(): void
    {
        $readModel = new SnapshotReadModel(
            $this->snapshotProvider,
            ['fqn_aggregate_type'],
        );

        $readModel->initialize();

        $events = $this->provideEvents();
        foreach ($events as $event) {
            $readModel->stack('stack', $event);
        }

        $this->assertCount(3, $readModel->getSnapshots());
        $this->assertSame($events, $readModel->getSnapshots());
    }

    public function testPersistEvents(): void
    {
        $readModel = new SnapshotReadModel(
            $this->snapshotProvider,
            ['fqn_aggregate_type'],
        );

        $readModel->initialize();

        $events = $this->provideEvents();
        foreach ($events as $event) {
            $readModel->stack('stack', $event);
        }

        $this->snapshotProvider
            ->expects($this->exactly(3))
            ->method('store')
            ->willReturnCallback(fn ($event) => $this->assertInstanceOf(SomeEvent::class, $event));

        $readModel->persist();

        $this->assertCount(0, $readModel->getSnapshots());
    }

    public function testReset(): void
    {
        $readModel = new SnapshotReadModel(
            $this->snapshotProvider,
            ['fqn_aggregate_type'],
        );

        $readModel->initialize();

        $events = $this->provideEvents();
        foreach ($events as $event) {
            $readModel->stack('stack', $event);
        }

        $this->snapshotProvider
            ->expects($this->once())
            ->method('deleteAll')
            ->willReturnCallback(fn ($aggregateType) => $this->assertEquals('fqn_aggregate_type', $aggregateType));

        $readModel->reset();
    }

    public function testResetManyAggregateType(): void
    {
        $readModel = new SnapshotReadModel(
            $this->snapshotProvider,
            ['fqn_aggregate_type', 'another_fqn_aggregate_type'],
        );

        $readModel->initialize();

        $events = $this->provideEvents();
        foreach ($events as $event) {
            $readModel->stack('stack', $event);
        }

        $this->snapshotProvider
            ->expects($this->exactly(2))
            ->method('deleteAll')
            ->willReturnCallback(
                fn ($aggregateType) => $this->assertContains(
                    $aggregateType, ['fqn_aggregate_type', 'another_fqn_aggregate_type']
                )
            );

        $readModel->reset();
    }

    public function testDown(): void
    {
        $readModel = new SnapshotReadModel(
            $this->snapshotProvider,
            ['fqn_aggregate_type'],
        );

        $readModel->initialize();

        $events = $this->provideEvents();
        foreach ($events as $event) {
            $readModel->stack('stack', $event);
        }

        $this->snapshotProvider
            ->expects($this->once())
            ->method('deleteAll')
            ->willReturnCallback(fn ($aggregateType) => $this->assertEquals('fqn_aggregate_type', $aggregateType));

        $readModel->down();
    }

    public function testDownManyAggregateType(): void
    {
        $readModel = new SnapshotReadModel(
            $this->snapshotProvider,
            ['fqn_aggregate_type', 'another_fqn_aggregate_type'],
        );

        $readModel->initialize();

        $events = $this->provideEvents();
        foreach ($events as $event) {
            $readModel->stack('stack', $event);
        }

        $this->snapshotProvider
            ->expects($this->exactly(2))
            ->method('deleteAll')
            ->willReturnCallback(
                fn ($aggregateType) => $this->assertContains(
                    $aggregateType, ['fqn_aggregate_type', 'another_fqn_aggregate_type']
                )
            );

        $readModel->down();
    }

    public function testGetSnapshots(): void
    {
        $readModel = new SnapshotReadModel($this->snapshotProvider, ['fqn_aggregate_type']);

        $this->assertEquals([], $readModel->getSnapshots());
        $this->assertSame($readModel->getSnapshots(), $readModel->getSnapshots());
    }

    private function provideEvents(): array
    {
        return [
            SomeEvent::fromContent(['foo' => 'bar'])->withHeader(EventHeader::AGGREGATE_TYPE, 'aggregate_type'),
            SomeEvent::fromContent(['foo' => 'baz'])->withHeader(EventHeader::AGGREGATE_TYPE, 'aggregate_type'),
            SomeEvent::fromContent(['bar' => 'baz'])->withHeader(EventHeader::AGGREGATE_TYPE, 'another_aggregate_type'),
        ];
    }
}
