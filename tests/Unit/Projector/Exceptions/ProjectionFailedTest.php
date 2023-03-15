<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Exceptions;

use RuntimeException;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;

#[CoversClass(ProjectionFailed::class)]
final class ProjectionFailedTest extends UnitTestCase
{
    private string $streamName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->streamName = 'transaction';
    }

    #[Test]
    public function it_format_exception(): void
    {
        $exceptionRaised = new RuntimeException('foo');

        $exception = ProjectionFailed::fromProjectionException($exceptionRaised);

        $this->assertInstanceOf(ProjectionFailed::class, $exception);
        $this->assertEquals('foo', $exception->getMessage());
        $this->assertEquals($exception->getCode(), $exceptionRaised->getCode());
    }

    #[Test]
    public function it_format_exception_with_message_name(): void
    {
        $exceptionRaised = new RuntimeException('foo');

        $exception = ProjectionFailed::fromProjectionException($exceptionRaised, 'bar');

        $this->assertInstanceOf(ProjectionFailed::class, $exception);
        $this->assertEquals('bar', $exception->getMessage());
        $this->assertEquals($exception->getCode(), $exceptionRaised->getCode());
    }

    #[Test]
    public function it_raise_exception_on_create(): void
    {
        $exception = ProjectionFailed::failedOnCreate($this->streamName);

        $this->assertEquals("Unable to create projection for stream name: $this->streamName", $exception->getMessage());
    }

    #[Test]
    public function it_raise_exception_on_stop(): void
    {
        $exception = ProjectionFailed::failedOnStop($this->streamName);

        $this->assertEquals("Unable to stop projection for stream name: $this->streamName", $exception->getMessage());
    }

    #[Test]
    public function it_raise_exception_on_start_again(): void
    {
        $exception = ProjectionFailed::failedOnStartAgain($this->streamName);

        $this->assertEquals("Unable to restart projection for stream name: $this->streamName", $exception->getMessage());
    }

    #[Test]
    public function it_raise_exception_on_persist(): void
    {
        $exception = ProjectionFailed::failedOnPersist($this->streamName);

        $this->assertEquals("Unable to persist projection for stream name: $this->streamName", $exception->getMessage());
    }

    #[Test]
    public function it_raise_exception_on_reset(): void
    {
        $exception = ProjectionFailed::failedOnReset($this->streamName);

        $this->assertEquals("Unable to reset projection for stream name: $this->streamName", $exception->getMessage());
    }

    #[Test]
    public function it_raise_exception_on_delete(): void
    {
        $exception = ProjectionFailed::failedOnDelete($this->streamName);

        $this->assertEquals("Unable to delete projection for stream name: $this->streamName", $exception->getMessage());
    }

    #[Test]
    public function it_raise_exception_on_projection_already_running(): void
    {
        $exception = ProjectionFailed::failedOnAcquireLock($this->streamName);

        $this->assertInstanceOf(ProjectionAlreadyRunning::class, $exception);

        $message = "Acquiring lock failed for stream name: $this->streamName: ";
        $message .= 'another projection process is already running or ';
        $message .= 'wait till the stopping process complete';

        $this->assertEquals($message, $exception->getMessage());
    }

    #[Test]
    public function it_raise_exception_on_update_lock(): void
    {
        $exception = ProjectionFailed::failedOnUpdateLock($this->streamName);

        $this->assertEquals("Unable to update projection lock for stream name: $this->streamName", $exception->getMessage());
    }

    #[Test]
    public function it_raise_exception_on_release_lock(): void
    {
        $exception = ProjectionFailed::failedOnReleaseLock($this->streamName);

        $this->assertEquals("Unable to release projection lock for stream name: $this->streamName", $exception->getMessage());
    }

    #[Test]
    public function it_raise_exception_on_update_projection_status(): void
    {
        $raiseException = new RuntimeException('foo');

        $exception = ProjectionFailed::failedOnUpdateStatus($this->streamName, ProjectionStatus::DELETING, $raiseException);

        $this->assertEquals("Unable to update projection status for stream name $this->streamName and status deleting", $exception->getMessage());
    }
}
