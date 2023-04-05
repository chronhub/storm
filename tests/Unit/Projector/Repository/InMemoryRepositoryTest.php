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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Throwable;

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
        $this->repository->expects(self::once())->method('create')->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->create());
    }

    public function testQueryFailureRaisedWhenCreateProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository->expects(self::once())
            ->method('create')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->create();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    public function testExceptionRaisedWhenCreateProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to create projection for stream name: some_stream_name');

        $this->repository->expects(self::once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository->expects(self::once())
            ->method('create')
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->create();
    }

    /***/
    public function testStopProjection(): void
    {
        $this->repository->expects(self::once())
            ->method('stop')
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->stop());
    }

    public function testQueryFailureRaisedWhenStopProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository->expects(self::once())
            ->method('stop')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->stop();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    public function testExceptionRaisedWhenStopProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to stop projection for stream name: some_stream_name');

        $this->repository->expects(self::once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository->expects(self::once())
            ->method('stop')
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->stop();
    }

    /***/
    public function testStartAgainProjection(): void
    {
        $this->repository->expects(self::once())
            ->method('startAgain')
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->startAgain());
    }

    public function testQueryFailureRaisedWhenRestartProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository->expects(self::once())
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

        $this->repository->expects(self::once())
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
        $this->repository->expects(self::once())
            ->method('persist')
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->persist());
    }

    public function testQueryFailureRaisedWhenPersistProjection(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository->expects(self::once())
            ->method('persist')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->persist();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    public function testExceptionRaisedWhenPersistProjection(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to persist projection for stream name: some_stream_name');

        $this->repository->expects(self::once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository->expects(self::once())
            ->method('persist')
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->persist();
    }

    /***/
    public function testResetProjection(): void
    {
        $this->repository->expects(self::once())
            ->method('reset')
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->reset());
    }

    public function testQueryFailureRaisedWhenResetProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository->expects(self::once())
            ->method('reset')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->reset();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    public function testExceptionRaisedWhenResetProjectionFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to reset projection for stream name: some_stream_name');

        $this->repository->expects(self::once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository->expects(self::once())
            ->method('reset')
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->reset();
    }

    /***/
    #[DataProvider('provideBoolean')]
    public function testDeleteProjection(bool $withEmittedEvents): void
    {
        $this->repository->expects(self::once())
            ->method('delete')
            ->with($withEmittedEvents)
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->delete($withEmittedEvents));
    }

    #[DataProvider('provideBoolean')]
    public function testQueryFailureRaisedWhenDeleteProjectionFailed(bool $withEmittedEvents): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository->expects(self::once())
            ->method('delete')
            ->with($withEmittedEvents)
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->delete($withEmittedEvents);
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    #[DataProvider('provideBoolean')]
    public function testExceptionRaisedWhenDeleteProjectionFailed(bool $withEmittedEvents): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to delete projection for stream name: some_stream_name');

        $this->repository->expects(self::once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository->expects(self::once())
            ->method('delete')
            ->with($withEmittedEvents)
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->delete($withEmittedEvents);
    }

    public function it_acquire_lock(): void
    {
        $this->repository->expects(self::once())
            ->method('acquireLock')
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->acquireLock());
    }

    public function testQueryFailureRaisedWhenAcquireLockFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository->expects(self::once())
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

        $this->repository->expects(self::once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository->expects(self::once())
            ->method('acquireLock')
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->acquireLock();
    }

    /***/
    public function testUpdateLock(): void
    {
        $this->repository->expects(self::once())
            ->method('updateLock')
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->updateLock());
    }

    public function testQueryFailureRaisedWhenUpdateLockFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository->expects(self::once())
            ->method('updateLock')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryRepository($this->repository);

        try {
            $connectionProvider->updateLock();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    public function testExceptionRaisedWhenUpdateLockFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to update projection lock for stream name: some_stream_name');

        $this->repository->expects(self::once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository->expects(self::once())
            ->method('updateLock')
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->updateLock();
    }

    public function testReleaseLock(): void
    {
        $this->repository->expects(self::once())
            ->method('releaseLock')
            ->willReturn(true);

        $connectionProvider = new InMemoryRepository($this->repository);

        $this->assertTrue($connectionProvider->releaseLock());
    }

    public function testQueryFailureRaisedWhenReleaseLockFailed(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->repository->expects(self::once())
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

        $this->repository->expects(self::once())
            ->method('projectionName')
            ->willReturn('some_stream_name');

        $this->repository->expects(self::once())
            ->method('releaseLock')
            ->willReturn(false);

        $connectionProvider = new InMemoryRepository($this->repository);

        $connectionProvider->releaseLock();
    }

    public function testLoadProjectionStatus(): void
    {
        $this->repository->expects(self::once())->method('loadStatus')->willReturn(ProjectionStatus::RUNNING);

        $this->assertEquals(ProjectionStatus::RUNNING, (new InMemoryRepository($this->repository))->loadStatus());
    }

    #[DataProvider('provideBoolean')]
    public function testLoadProjectionState(bool $success): void
    {
        $this->repository->expects(self::once())->method('loadState')->willReturn($success);

        $this->assertEquals($success, (new InMemoryRepository($this->repository))->loadState());
    }

    #[DataProvider('provideBoolean')]
    public function testCheckProjectionExists(bool $projectionExists): void
    {
        $this->repository->expects(self::once())->method('exists')->willReturn($projectionExists);

        $this->assertEquals($projectionExists, (new InMemoryRepository($this->repository))->exists());
    }

    public function testGetProjectionName(): void
    {
        $this->repository->expects(self::once())
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
