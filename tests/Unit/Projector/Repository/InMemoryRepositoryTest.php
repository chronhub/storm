<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Repository;

use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Projector\Exceptions\InMemoryProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\InMemoryRepository;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Throwable;

#[CoversClass(InMemoryRepository::class)]
final class InMemoryRepositoryTest extends UnitTestCase
{
    private ProjectionRepositoryInterface|MockObject $repository;

    private Throwable $someException;

    public function setUp(): void
    {
        $this->repository = $this->createMock(ProjectionRepositoryInterface::class);
        $this->someException = new RuntimeException('foo');
    }

    public function testCreateProjection(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with(ProjectionStatus::RUNNING)
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->create(ProjectionStatus::RUNNING));
    }

    public function testQueryFailureRaisedWhenCreateProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with(ProjectionStatus::RUNNING)
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->create(ProjectionStatus::RUNNING);
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    public function testExceptionRaisedWhenCreateProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to create projection for stream name: some_stream_name');

        $this->repository
            ->expects(self::once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with(ProjectionStatus::RUNNING)
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->create(ProjectionStatus::RUNNING);
    }

    /***/
    public function testStopProjection(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('stop')
            ->with(['bar' => 1], ['foo'])
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->stop(['bar' => 1], ['foo']));
    }

    public function testQueryFailureRaisedWhenStopProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository
            ->expects($this->once())
            ->method('stop')
            ->with(['bar' => 1], ['foo'])
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->stop(['bar' => 1], ['foo']);
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    public function testExceptionRaisedWhenStopProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to stop projection for stream name: some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('stop')
            ->with(['bar' => 1], ['foo'])
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->stop(['bar' => 1], ['foo']);
    }

    /***/
    public function testStartAgainProjection(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('startAgain')
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->startAgain());
    }

    public function testQueryFailureRaisedWhenRestartProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository
            ->expects($this->once())
            ->method('startAgain')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->startAgain();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    public function testExceptionRaisedWhenRestartProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to restart projection for stream name: some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository->expects(self::once())
            ->method('startAgain')
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->startAgain();
    }

    /***/
    public function testPersistProjection(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('persist')
            ->with(['bar' => 1], ['foo'])
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->persist(['bar' => 1], ['foo']));
    }

    public function testQueryFailureRaisedWhenPersistProjection(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository
            ->expects($this->once())
            ->method('persist')
            ->with(['bar' => 1], ['foo'])
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->persist(['bar' => 1], ['foo']);
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    public function testExceptionRaisedWhenPersistProjection(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to persist projection for stream name: some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('persist')
            ->with(['bar' => 1], ['foo'])
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->persist(['bar' => 1], ['foo']);
    }

    /***/
    public function testResetProjection(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('reset')
            ->with(['bar' => 1], ['foo'], ProjectionStatus::RUNNING)
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->reset(['bar' => 1], ['foo'], ProjectionStatus::RUNNING));
    }

    public function testQueryFailureRaisedWhenResetProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository
            ->expects($this->once())
            ->method('reset')
            ->with(['bar' => 1], ['foo'], ProjectionStatus::RUNNING)
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->reset(['bar' => 1], ['foo'], ProjectionStatus::RUNNING);
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    public function testExceptionRaisedWhenResetProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to reset projection for stream name: some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('reset')
            ->with(['bar' => 1], ['foo'], ProjectionStatus::RUNNING)
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->reset(['bar' => 1], ['foo'], ProjectionStatus::RUNNING);
    }

    public function testDeleteProjection(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->delete());
    }

    public function testQueryFailureRaisedWhenDeleteProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->delete();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    public function testExceptionRaisedWhenDeleteProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to delete projection for stream name: some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->delete();
    }

    public function testAcquireLock(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('acquireLock')
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->acquireLock());
    }

    public function testQueryFailureRaisedWhenAcquireLockFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository
            ->expects($this->once())
            ->method('acquireLock')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->acquireLock();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    public function testProjectionAlreadyRunning(): void
    {
        $this->expectException(ProjectionAlreadyRunning::class);
        $this->expectExceptionMessage('Acquiring lock failed for stream name: some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('acquireLock')
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->acquireLock();
    }

    public function testUpdateLock(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('updateLock')
            ->with(['foo' => 10, 'bar' => 25])
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->updateLock(['foo' => 10, 'bar' => 25]));
    }

    public function testQueryFailureRaisedWhenUpdateLockFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository
            ->expects($this->once())
            ->method('updateLock')
            ->with(['foo' => 110, 'bar' => 251])
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->updateLock(['foo' => 110, 'bar' => 251]);
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    public function testExceptionRaisedWhenUpdateLockFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to update projection lock for stream name: some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('updateLock')
            ->with(['foo' => 10, 'bar' => 25])
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->updateLock(['foo' => 10, 'bar' => 25]);
    }

    public function testReleaseLock(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('releaseLock')
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->releaseLock());
    }

    public function testQueryFailureRaisedWhenReleaseLockFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository
            ->expects($this->once())
            ->method('releaseLock')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->releaseLock();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    public function testExceptionRaisedWhenReleaseLockFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to release projection lock for stream name: some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository
            ->expects($this->once())
            ->method('releaseLock')
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->releaseLock();
    }

    public function testLoadProjectionStatus(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('loadStatus')
            ->willReturn(ProjectionStatus::RUNNING);

        $this->assertEquals(
            ProjectionStatus::RUNNING,
            (new InMemoryRepository($this->repository))->loadStatus()
        );
    }

    public function testLoadProjectionState(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('loadState')
            ->willReturn(['stream_positions', 'projection_state']);

        $this->assertEquals(
            ['stream_positions', 'projection_state'],
            (new InMemoryRepository($this->repository))->loadState()
        );
    }

    #[DataProvider('provideBoolean')]
    public function testCheckProjectionExists(bool $projectionExists): void
    {
        $this->repository->expects($this->once())->method('exists')->willReturn($projectionExists);

        $this->assertEquals($projectionExists, (new InMemoryRepository($this->repository))->exists());
    }

    public function testGetProjectionName(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('projectionName')
            ->willReturn('foo');

        $this->assertEquals('foo', (new InMemoryRepository($this->repository))->projectionName());
    }

    public static function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
