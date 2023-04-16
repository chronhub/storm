<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Projection;

use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\ProjectorManager;
use Throwable;

final class UpdateFailedProjectorManagerTest extends InMemoryProjectorManagerTestCase
{
    public function testExceptionRaisedOnStopWhenUpdateFailed(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        try {
            $manager->stop('projection_name');
        } catch (Throwable $exception) {
            $this->assertInstanceOf(ProjectionFailed::class, $exception);
            $this->assertSame(
                'Unable to update projection status for stream name projection_name and status stopping',
                $exception->getMessage()
            );

            $previous = $exception->getPrevious();

            $this->assertInstanceOf(ProjectionNotFound::class, $previous);
        }
    }

    public function testExceptionRaisedOnResetWhenUpdateFailed(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        try {
            $manager->reset('projection_name');
        } catch (Throwable $exception) {
            $this->assertInstanceOf(ProjectionFailed::class, $exception);
            $this->assertSame(
                'Unable to update projection status for stream name projection_name and status resetting',
                $exception->getMessage()
            );

            $previous = $exception->getPrevious();

            $this->assertInstanceOf(ProjectionNotFound::class, $previous);
        }
    }

    public function testExceptionRaisedOnDeleteWhenUpdateFailed(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        try {
            $manager->delete('projection_name', false);
        } catch (Throwable $exception) {
            $this->assertInstanceOf(ProjectionFailed::class, $exception);
            $this->assertSame(
                'Unable to update projection status for stream name projection_name and status deleting',
                $exception->getMessage()
            );

            $previous = $exception->getPrevious();

            $this->assertInstanceOf(ProjectionNotFound::class, $previous);
        }
    }

    public function testExceptionRaisedOnDeleteWithEmittedEventsWhenUpdateFailed(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        try {
            $manager->delete('projection_name', true);
        } catch (Throwable $exception) {
            $this->assertInstanceOf(ProjectionFailed::class, $exception);
            $this->assertSame(
                'Unable to update projection status for stream name projection_name and status deleting_with_emitted_events',
                $exception->getMessage()
            );

            $previous = $exception->getPrevious();

            $this->assertInstanceOf(ProjectionNotFound::class, $previous);
        }
    }
}
