<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Exceptions;

use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Tests\UnitTestCase;
use RuntimeException;

final class ProjectionFailedTest extends UnitTestCase
{
    private string $streamName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->streamName = 'transaction';
    }

    public function TestFormatExceptionMessage(): void
    {
        $exceptionRaised = new RuntimeException('foo');

        $exception = ProjectionFailed::fromProjectionException($exceptionRaised);

        $this->assertInstanceOf(ProjectionFailed::class, $exception);
        $this->assertEquals('foo', $exception->getMessage());
        $this->assertEquals($exception->getCode(), $exceptionRaised->getCode());
    }

    public function TestFormatExceptionMessageWithGivenMessageName(): void
    {
        $exceptionRaised = new RuntimeException('foo');

        $exception = ProjectionFailed::fromProjectionException($exceptionRaised, 'bar');

        $this->assertInstanceOf(ProjectionFailed::class, $exception);
        $this->assertEquals('bar', $exception->getMessage());
        $this->assertEquals($exception->getCode(), $exceptionRaised->getCode());
    }

    public function testExceptionRaisedOnCreate(): void
    {
        $exception = ProjectionFailed::failedOnCreate($this->streamName);

        $this->assertEquals("Unable to create projection for stream name: $this->streamName", $exception->getMessage());
    }

    public function testExceptionRaisedOnStop(): void
    {
        $exception = ProjectionFailed::failedOnStop($this->streamName);

        $this->assertEquals("Unable to stop projection for stream name: $this->streamName", $exception->getMessage());
    }

    public function testExceptionRaisedOnStartAgain(): void
    {
        $exception = ProjectionFailed::failedOnStartAgain($this->streamName);

        $this->assertEquals("Unable to restart projection for stream name: $this->streamName", $exception->getMessage());
    }

    public function testExceptionRaisedOnPersist(): void
    {
        $exception = ProjectionFailed::failedOnPersist($this->streamName);

        $this->assertEquals("Unable to persist projection for stream name: $this->streamName", $exception->getMessage());
    }

    public function testExceptionRaisedOnReset(): void
    {
        $exception = ProjectionFailed::failedOnReset($this->streamName);

        $this->assertEquals("Unable to reset projection for stream name: $this->streamName", $exception->getMessage());
    }

    public function testExceptionRaisedOnDelete(): void
    {
        $exception = ProjectionFailed::failedOnDelete($this->streamName);

        $this->assertEquals("Unable to delete projection for stream name: $this->streamName", $exception->getMessage());
    }

    public function testExceptionRaisedOnAcquireLockedFailure(): void
    {
        $exception = ProjectionFailed::failedOnAcquireLock($this->streamName);

        $this->assertInstanceOf(ProjectionAlreadyRunning::class, $exception);

        $message = "Acquiring lock failed for stream name: $this->streamName: ";
        $message .= 'another projection process is already running or ';
        $message .= 'wait till the stopping process complete';

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionRaisedUpdateLockFailure(): void
    {
        $exception = ProjectionFailed::failedOnUpdateLock($this->streamName);

        $this->assertEquals("Unable to update projection lock for stream name: $this->streamName", $exception->getMessage());
    }

    public function testExceptionRaisedOnReleaseLockFailure(): void
    {
        $exception = ProjectionFailed::failedOnReleaseLock($this->streamName);

        $this->assertEquals("Unable to release projection lock for stream name: $this->streamName", $exception->getMessage());
    }

    public function testExceptionRaisedOnUpdateProjectionStatus(): void
    {
        $raiseException = new RuntimeException('foo');

        $exception = ProjectionFailed::failedOnUpdateStatus($this->streamName, ProjectionStatus::DELETING, $raiseException);

        $this->assertEquals("Unable to update projection status for stream name $this->streamName and status deleting", $exception->getMessage());
    }
}
