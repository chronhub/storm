<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Throwable;
use RuntimeException;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Chronhub\Storm\Contracts\Projector\Store;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\InMemoryStore;
use Chronhub\Storm\Projector\Exceptions\InMemoryProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;

final class InMemoryStoreTest extends UnitTestCase
{
    private Store|MockObject $store;

    private Throwable $someException;

    public function setUp(): void
    {
        $this->store = $this->createMock(Store::class);
        $this->someException = new RuntimeException('foo');
    }

    /**
     * @test
     */
    public function it_create(): void
    {
        $this->store->expects(self::once())->method('create')->willReturn(true);

        $connectionProvider = new InMemoryStore($this->store);

        $this->assertTrue($connectionProvider->create());
    }

    /**
     * @test
     */
    public function it_raise_query_failure_on_create(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->store->expects(self::once())
            ->method('create')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryStore($this->store);

        try {
            $connectionProvider->create();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    /**
     * @test
     */
    public function it_raise_projection_failed_on_failed_created(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to create projection for stream name: some_stream_name');

        $this->store->expects(self::once())
            ->method('currentStreamName')
            ->willReturn('some_stream_name');

        $this->store->expects(self::once())
            ->method('create')
            ->willReturn(false);

        $connectionProvider = new InMemoryStore($this->store);

        $connectionProvider->create();
    }

    /***/

    /**
     * @test
     */
    public function it_stop(): void
    {
        $this->store->expects(self::once())
            ->method('stop')
            ->willReturn(true);

        $connectionProvider = new InMemoryStore($this->store);

        $this->assertTrue($connectionProvider->stop());
    }

    /**
     * @test
     */
    public function it_raise_query_failure_on_stop(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->store->expects(self::once())
            ->method('stop')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryStore($this->store);

        try {
            $connectionProvider->stop();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    /**
     * @test
     */
    public function it_raise_projection_failed_on_failed_stopped(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to stop projection for stream name: some_stream_name');

        $this->store->expects(self::once())
            ->method('currentStreamName')
            ->willReturn('some_stream_name');

        $this->store->expects(self::once())
            ->method('stop')
            ->willReturn(false);

        $connectionProvider = new InMemoryStore($this->store);

        $connectionProvider->stop();
    }

    /***/

    /**
     * @test
     */
    public function it_start_again(): void
    {
        $this->store->expects(self::once())
            ->method('startAgain')
            ->willReturn(true);

        $connectionProvider = new InMemoryStore($this->store);

        $this->assertTrue($connectionProvider->startAgain());
    }

    /**
     * @test
     */
    public function it_raise_query_failure_on_start_again(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->store->expects(self::once())
            ->method('startAgain')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryStore($this->store);

        try {
            $connectionProvider->startAgain();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    /**
     * @test
     */
    public function it_raise_projection_failed_on_failed_start_again(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to restart projection for stream name: some_stream_name');

        $this->store->expects(self::once())
            ->method('currentStreamName')
            ->willReturn('some_stream_name');

        $this->store->expects(self::once())
            ->method('startAgain')
            ->willReturn(false);

        $connectionProvider = new InMemoryStore($this->store);

        $connectionProvider->startAgain();
    }

    /***/

    /**
     * @test
     */
    public function it_persist(): void
    {
        $this->store->expects(self::once())
            ->method('persist')
            ->willReturn(true);

        $connectionProvider = new InMemoryStore($this->store);

        $this->assertTrue($connectionProvider->persist());
    }

    /**
     * @test
     */
    public function it_raise_query_failure_on_persist(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->store->expects(self::once())
            ->method('persist')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryStore($this->store);

        try {
            $connectionProvider->persist();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    /**
     * @test
     */
    public function it_raise_projection_failed_on_failed_persist(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to persist projection for stream name: some_stream_name');

        $this->store->expects(self::once())
            ->method('currentStreamName')
            ->willReturn('some_stream_name');

        $this->store->expects(self::once())
            ->method('persist')
            ->willReturn(false);

        $connectionProvider = new InMemoryStore($this->store);

        $connectionProvider->persist();
    }

    /***/

    /**
     * @test
     */
    public function it_reset(): void
    {
        $this->store->expects(self::once())
            ->method('reset')
            ->willReturn(true);

        $connectionProvider = new InMemoryStore($this->store);

        $this->assertTrue($connectionProvider->reset());
    }

    /**
     * @test
     */
    public function it_raise_query_failure_on_reset(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->store->expects(self::once())
            ->method('reset')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryStore($this->store);

        try {
            $connectionProvider->reset();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    /**
     * @test
     */
    public function it_raise_projection_failed_on_failed_reset(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to reset projection for stream name: some_stream_name');

        $this->store->expects(self::once())
            ->method('currentStreamName')
            ->willReturn('some_stream_name');

        $this->store->expects(self::once())
            ->method('reset')
            ->willReturn(false);

        $connectionProvider = new InMemoryStore($this->store);

        $connectionProvider->reset();
    }

    /***/

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_delete(bool $withEmittedEvents): void
    {
        $this->store->expects(self::once())
            ->method('delete')
            ->with($withEmittedEvents)
            ->willReturn(true);

        $connectionProvider = new InMemoryStore($this->store);

        $this->assertTrue($connectionProvider->delete($withEmittedEvents));
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_raise_query_failure_on_delete(bool $withEmittedEvents): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->store->expects(self::once())
            ->method('delete')
            ->with($withEmittedEvents)
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryStore($this->store);

        try {
            $connectionProvider->delete($withEmittedEvents);
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_raise_projection_failed_on_failed_delete(bool $withEmittedEvents): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to delete projection for stream name: some_stream_name');

        $this->store->expects(self::once())
            ->method('currentStreamName')
            ->willReturn('some_stream_name');

        $this->store->expects(self::once())
            ->method('delete')
            ->with($withEmittedEvents)
            ->willReturn(false);

        $connectionProvider = new InMemoryStore($this->store);

        $connectionProvider->delete($withEmittedEvents);
    }

    /**
     * @test
     */
    public function it_acquire_lock(): void
    {
        $this->store->expects(self::once())
            ->method('acquireLock')
            ->willReturn(true);

        $connectionProvider = new InMemoryStore($this->store);

        $this->assertTrue($connectionProvider->acquireLock());
    }

    /**
     * @test
     */
    public function it_raise_query_failure_on_acquire_lock(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->store->expects(self::once())
            ->method('acquireLock')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryStore($this->store);

        try {
            $connectionProvider->acquireLock();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    /**
     * @test
     */
    public function it_raise_projection_failed_on_failed_acquire_lock(): void
    {
        $this->expectException(ProjectionAlreadyRunning::class);
        $this->expectExceptionMessage('Acquiring lock failed for stream name: some_stream_name');

        $this->store->expects(self::once())
            ->method('currentStreamName')
            ->willReturn('some_stream_name');

        $this->store->expects(self::once())
            ->method('acquireLock')
            ->willReturn(false);

        $connectionProvider = new InMemoryStore($this->store);

        $connectionProvider->acquireLock();
    }

    /***/

    /**
     * @test
     */
    public function it_update_lock(): void
    {
        $this->store->expects(self::once())
            ->method('updateLock')
            ->willReturn(true);

        $connectionProvider = new InMemoryStore($this->store);

        $this->assertTrue($connectionProvider->updateLock());
    }

    /**
     * @test
     */
    public function it_raise_query_failure_on_update_lock(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->store->expects(self::once())
            ->method('updateLock')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryStore($this->store);

        try {
            $connectionProvider->updateLock();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    /**
     * @test
     */
    public function it_raise_projection_failed_on_failed_update_lock(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to update projection lock for stream name: some_stream_name');

        $this->store->expects(self::once())
            ->method('currentStreamName')
            ->willReturn('some_stream_name');

        $this->store->expects(self::once())
            ->method('updateLock')
            ->willReturn(false);

        $connectionProvider = new InMemoryStore($this->store);

        $connectionProvider->updateLock();
    }

    /**
     * @test
     */
    public function it_release_lock(): void
    {
        $this->store->expects(self::once())
            ->method('releaseLock')
            ->willReturn(true);

        $connectionProvider = new InMemoryStore($this->store);

        $this->assertTrue($connectionProvider->releaseLock());
    }

    /**
     * @test
     */
    public function it_raise_query_failure_on_release_lock(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);

        $this->store->expects(self::once())
            ->method('releaseLock')
            ->willThrowException($this->someException);

        $connectionProvider = new InMemoryStore($this->store);

        try {
            $connectionProvider->releaseLock();
        } catch (InMemoryProjectionFailed $e) {
            $this->assertEquals($this->someException, $e->getPrevious());

            throw $e;
        }
    }

    /**
     * @test
     */
    public function it_raise_projection_failed_on_failed_release_lock(): void
    {
        $this->expectException(InMemoryProjectionFailed::class);
        $this->expectExceptionMessage('Unable to release projection lock for stream name: some_stream_name');

        $this->store->expects(self::once())
            ->method('currentStreamName')
            ->willReturn('some_stream_name');

        $this->store->expects(self::once())
            ->method('releaseLock')
            ->willReturn(false);

        $connectionProvider = new InMemoryStore($this->store);

        $connectionProvider->releaseLock();
    }

    /**
     * @test
     */
    public function it_load_status(): void
    {
        $this->store->expects(self::once())->method('loadStatus')->willReturn(ProjectionStatus::RUNNING);

        $this->assertEquals(ProjectionStatus::RUNNING, (new InMemoryStore($this->store))->loadStatus());
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_load_state(bool $success): void
    {
        $this->store->expects(self::once())->method('loadState')->willReturn($success);

        $this->assertEquals($success, (new InMemoryStore($this->store))->loadState());
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_assert_projection_exists(bool $projectionExists): void
    {
        $this->store->expects(self::once())->method('exists')->willReturn($projectionExists);

        $this->assertEquals($projectionExists, (new InMemoryStore($this->store))->exists());
    }

    /**
     * @test
     */
    public function it_access_current_stream_name(): void
    {
        $this->store->expects(self::once())
            ->method('currentStreamName')
            ->willReturn('foo');

        $this->assertEquals('foo', (new InMemoryStore($this->store))->currentStreamName());
    }

    public function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
