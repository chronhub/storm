<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\InMemoryProjectionProvider;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

final class InMemoryProjectionProviderTest extends UnitTestCase
{
    private InMemoryProjectionProvider $projectionProvider;

    private SystemClock|MockObject $clock;

    protected function setUp(): void
    {
        $this->clock = $this->createMock(SystemClock::class);
        $this->projectionProvider = new InMemoryProjectionProvider($this->clock);
    }

    #[DataProvider('provideStatus')]
    public function testCreateProjection(string $status): void
    {
        $this->assertTrue($this->projectionProvider->createProjection('projection1', $status));
        $this->assertFalse($this->projectionProvider->createProjection('projection1', $status));
    }

    public function testUpdateProjection(): void
    {
        $this->projectionProvider->createProjection('projection1', 'status1');

        $this->assertTrue($this->projectionProvider->updateProjection('projection1', ['state' => 'state1']));
        $this->assertFalse($this->projectionProvider->updateProjection('projection2', ['state' => 'state1']));

        $this->expectException(InvalidArgumentException::class);
        $this->projectionProvider->updateProjection('projection1', ['invalid_field' => 'value']);
    }

    public function testDeleteProjection(): void
    {
        $this->assertFalse($this->projectionProvider->exists('projection1'));

        $this->projectionProvider->createProjection('projection1', 'status1');

        $this->assertTrue($this->projectionProvider->exists('projection1'));

        $this->assertTrue($this->projectionProvider->deleteProjection('projection1'));
        $this->assertFalse($this->projectionProvider->deleteProjection('projection2'));
    }

    public function testAcquireLock(): void
    {
        $this->projectionProvider->createProjection('projection1', 'status1');

        $this->assertTrue($this->projectionProvider->acquireLock('projection1', 'status2', 'locked_until2', '2023-04-03 15:00:00'));
        $this->assertFalse($this->projectionProvider->acquireLock('projection2', 'status2', 'locked_until2', '2023-04-03 15:00:00'));
    }

    public function testDoesNotAcquireLockWhenCurrentTimeIsGreaterThanLock(): void
    {
        $this->clock
            ->expects($this->once())
            ->method('isGreaterThan')
            ->with('2024-04-03 15:00:00', 'locked_until2')
            ->willReturn(false);

        $this->projectionProvider->createProjection('projection1', 'status1');

        $this->assertTrue($this->projectionProvider->acquireLock('projection1', 'status2', 'locked_until2', '2024-04-03 15:00:00'));
        $this->assertFalse($this->projectionProvider->acquireLock('projection1', 'status2', 'locked_until2', '2024-04-03 15:00:00'));
    }

    public function testRetrieve(): void
    {
        $this->projectionProvider->createProjection('projection1', 'status1');

        $projection = $this->projectionProvider->retrieve('projection1');
        $this->assertInstanceOf(ProjectionModel::class, $projection);
        $this->assertEquals('projection1', $projection->name());
    }

    public function testFilterByNames(): void
    {
        $this->projectionProvider->createProjection('projection1', 'status1');
        $this->projectionProvider->createProjection('projection2', 'status2');

        $this->assertEquals(['projection1'], $this->projectionProvider->filterByNames('projection1'));
        $this->assertEquals(['projection1', 'projection2'], $this->projectionProvider->filterByNames('projection1', 'projection2'));
        $this->assertEquals([], $this->projectionProvider->filterByNames('projection3'));
    }

    public function testExists(): void
    {
        $this->assertFalse($this->projectionProvider->exists('projection1'));

        $this->projectionProvider->createProjection('projection1', 'status1');

        $this->assertTrue($this->projectionProvider->exists('projection1'));
        $this->assertFalse($this->projectionProvider->exists('projection2'));
    }

    public static function provideStatus(): Generator
    {
        yield [ProjectionStatus::IDLE->value];
        yield [ProjectionStatus::RUNNING->value];
        yield [ProjectionStatus::RESETTING->value];
        yield [ProjectionStatus::DELETING->value];
        yield [ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value];
    }
}
