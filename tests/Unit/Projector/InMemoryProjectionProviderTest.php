<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\InMemoryProjectionProvider;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(InMemoryProjectionProvider::class)]
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
    }

    #[DataProvider('provideStatus')]
    public function testExceptionRaisedWhenCreateProjection(string $status): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Projection projection1 already exists');

        $this->assertTrue($this->projectionProvider->createProjection('projection1', $status));

        $this->projectionProvider->createProjection('projection1', $status);
    }

    public function testUpdateProjection(): void
    {
        $this->projectionProvider->createProjection('projection1', 'status1');

        $this->assertTrue($this->projectionProvider->updateProjection(
            'projection1',
            [
                'state' => 'count:1',
                'position' => 'foo:1',
                'status' => 'running',
                'locked_until' => '2023-04-03 15:00:00',
            ]
        ));

        $projection = $this->projectionProvider->retrieve('projection1');

        $this->assertSame('count:1', $projection->state());
        $this->assertSame('foo:1', $projection->positions());
        $this->assertSame('running', $projection->status());
        $this->assertSame('2023-04-03 15:00:00', $projection->lockedUntil());
    }

    public function testExceptionRaisedOnUpdateProjectionWithInvalidField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid projection field(s) [invalid_field] for projection projection1');

        $this->projectionProvider->createProjection('projection1', 'status1');

        $this->assertTrue($this->projectionProvider->updateProjection('projection1', ['state' => 'state1']));

        $this->projectionProvider->updateProjection('projection1', ['invalid_field' => 'value']);
    }

    public function testDeleteProjection(): void
    {
        $this->expectException(ProjectionNotFound::class);
        $this->assertFalse($this->projectionProvider->exists('projection1'));

        $this->projectionProvider->createProjection('projection1', 'status1');

        $this->assertTrue($this->projectionProvider->exists('projection1'));

        $this->assertTrue($this->projectionProvider->deleteProjection('projection1'));

        $this->projectionProvider->deleteProjection('projection2');
    }

    public function testAcquireLock(): void
    {
        $this->projectionProvider->createProjection('projection1', 'status1');

        $this->assertTrue($this->projectionProvider->acquireLock('projection1', 'status2', 'locked_until2'));
    }

    public function testExceptionRaisedWhenAcquireLockOnProjectionNotFound(): void
    {
        $this->expectException(ProjectionNotFound::class);

        $this->projectionProvider->createProjection('projection1', 'status1');

        $this->projectionProvider->acquireLock('projection2', 'status2', 'locked_until2');
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
