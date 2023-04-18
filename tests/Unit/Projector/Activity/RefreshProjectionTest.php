<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Projector\Activity\RefreshProjection;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class RefreshProjectionTest extends UnitTestCase
{
    private PersistentSubscriptionInterface|MockObject $subscription;

    public function setUp(): void
    {
        $this->subscription = $this->createMock(PersistentSubscriptionInterface::class);
    }

    public function testKeepWorkflowOnStop(): void
    {
        $activity = new RefreshProjection();

        $this->assertProjectionStatus(ProjectionStatus::STOPPING);

        $this->subscription->expects($this->never())->method('boundState');
        $this->subscription->expects($this->once())->method('close');

        $this->assertStreamPositions();

        $next = $activity($this->subscription, static fn () => true);

        $this->assertTrue($next);
    }

    public function testKeepWorkflowOnReset(): void
    {
        $activity = new RefreshProjection();

        $this->assertProjectionStatus(ProjectionStatus::RESETTING);

        $this->subscription->expects($this->once())->method('revise');
        $this->subscription->expects($this->never())->method('restart');

        $this->assertStreamPositions();

        $next = $activity($this->subscription, static fn () => true);

        $this->assertTrue($next);
    }

    public function testKeepWorkflowOnDelete(): void
    {
        $activity = new RefreshProjection();

        $this->assertProjectionStatus(ProjectionStatus::DELETING);

        $this->subscription->expects($this->once())->method('discard')->with(false);

        $this->assertStreamPositions();

        $next = $activity($this->subscription, static fn () => true);

        $this->assertTrue($next);
    }

    public function testKeepWorkflowOnDeleteWithEmittedEvents(): void
    {
        $activity = new RefreshProjection();

        $this->assertProjectionStatus(ProjectionStatus::DELETING_WITH_EMITTED_EVENTS);

        $this->subscription->expects($this->once())->method('discard')->with(true);

        $this->assertStreamPositions();

        $next = $activity($this->subscription, static fn () => true);

        $this->assertTrue($next);
    }

    public function testKeepWorkflowOnDiscoverStatusIdle(): void
    {
        $activity = new RefreshProjection();

        $this->assertProjectionStatus(ProjectionStatus::IDLE);

        $this->assertStreamPositions();

        $next = $activity($this->subscription, static fn () => true);

        $this->assertTrue($next);
    }

    public function testKeepWorkflowOnDiscoverStatusRunning(): void
    {
        $activity = new RefreshProjection();

        $this->assertProjectionStatus(ProjectionStatus::IDLE);

        $this->assertStreamPositions();

        $next = $activity($this->subscription, static fn () => true);

        $this->assertTrue($next);
    }

    private function assertProjectionStatus(ProjectionStatus $status): void
    {
        $this->subscription
            ->expects($this->once())
            ->method('sprint')
            ->willReturn(new Sprint());

        $this->subscription
            ->expects($this->once())
            ->method('disclose')
            ->willReturn($status);
    }

    private function assertStreamPositions(): void
    {
        // todo test provider should be called from all and category
        // as it will merge streams positions discovered
        $provider = $this->createMock(EventStreamProvider::class);
        $streamPosition = new StreamPosition($provider);

        $context = new Context();
        $context->fromStreams('stream1', 'stream2');

        $this->subscription
            ->expects($this->once())
            ->method('context')
            ->willReturn($context);

        $this->subscription
            ->expects($this->once())
            ->method('streamPosition')
            ->willReturn($streamPosition);
    }
}
