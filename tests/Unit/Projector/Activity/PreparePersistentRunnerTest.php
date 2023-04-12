<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Projector\Activity\PreparePersistentRunner;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(PreparePersistentRunner::class)]
class PreparePersistentRunnerTest extends UnitTestCase
{
    private PersistentSubscriptionInterface|MockObject $subscription;

    public function setUp(): void
    {
        $this->subscription = $this->createMock(PersistentSubscriptionInterface::class);
    }

    public function testInstance(): void
    {
        $activity = new PreparePersistentRunner();

        $this->assertFalse($activity->isInitialized());
    }

    #[DataProvider('provideStatusesWhichStopOnFirstExecution')]
    public function testStopOnFirstExecution(ProjectionStatus $stopOnFirstExecution): void
    {
        $activity = new PreparePersistentRunner();

        $this->assertFalse($activity->isInitialized());

        $this->subscription
            ->expects($this->once())
            ->method('sprint')
            ->willReturn(new Sprint());

        $this->subscription
            ->expects($this->once())
            ->method('disclose')
            ->willReturn($stopOnFirstExecution);

        $this->subscription->expects($this->never())->method('rise');

        $next = $activity($this->subscription, fn () => true);

        $this->assertTrue($activity->isInitialized());
        $this->assertTrue($next);
    }

    #[DataProvider('provideStatusesWhichKeepRunningOnFirstExecution')]
    public function testKeepWorkflowOnFirstExecution(ProjectionStatus $keepRunning): void
    {
        $activity = new PreparePersistentRunner();

        $this->assertFalse($activity->isInitialized());

        $this->subscription
            ->expects($this->once())
            ->method('sprint')
            ->willReturn(new Sprint());

        $this->subscription
            ->expects($this->once())
            ->method('disclose')
            ->willReturn($keepRunning);

        $this->subscription->expects($this->once())->method('rise');

        $next = $activity($this->subscription, fn () => true);

        $this->assertTrue($activity->isInitialized());
        $this->assertTrue($next);
    }

    public function testResetWhenKeepRunningAndDoesNotRestartProjection(): void
    {
        $activity = new PreparePersistentRunner();

        $this->assertFalse($activity->isInitialized());

        $sprint = new Sprint();
        $sprint->runInBackground(true);

        $this->subscription
            ->expects($this->once())
            ->method('sprint')
            ->willReturn($sprint);

        $this->subscription
            ->expects($this->once())
            ->method('disclose')
            ->willReturn(ProjectionStatus::RESETTING);

        $this->subscription->expects($this->once())->method('revise');
        $this->subscription->expects($this->once())->method('rise');
        // restarting should be done in UpdateProjectionStatus
        $this->subscription->expects($this->never())->method('restart');

        $next = $activity($this->subscription, fn () => true);

        $this->assertTrue($activity->isInitialized());
        $this->assertTrue($next);

        $activity($this->subscription, fn () => true);
    }

    public static function provideStatusesWhichStopOnFirstExecution(): array
    {
        return [
            [ProjectionStatus::STOPPING, true],
            [ProjectionStatus::DELETING, true],
            [ProjectionStatus::DELETING_WITH_EMITTED_EVENTS, true],
        ];
    }

    public static function provideStatusesWhichKeepRunningOnFirstExecution(): array
    {
        return [
            [ProjectionStatus::RESETTING, false],
            [ProjectionStatus::IDLE, false],
            [ProjectionStatus::RUNNING, false],
        ];
    }
}
