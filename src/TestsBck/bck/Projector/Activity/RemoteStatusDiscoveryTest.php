<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Projector\Activity\RemoteStatusDiscovery;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(RemoteStatusDiscovery::class)]
final class RemoteStatusDiscoveryTest extends UnitTestCase
{
    private PersistentSubscriptionInterface|MockObject $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscription = $this->createMock(PersistentSubscriptionInterface::class);
    }

    #[DataProvider('provideBoolean')]
    public function testMarkAsStopOn(bool $firstExecution): void
    {
        $this->subscription->expects($this->exactly(2))->method('disclose')->willReturn(ProjectionStatus::STOPPING);

        $firstExecution
            ? $this->subscription->expects($this->exactly(2))->method('synchronise')
            : $this->subscription->expects($this->never())->method('synchronise');

        $this->subscription->expects($this->exactly(2))->method('close');

        $instance = $this->newInstance();

        $this->assertSame($firstExecution, $instance->recover($firstExecution, false));
        $this->assertSame($firstExecution, $instance->recover($firstExecution, true));
    }

    #[DataProvider('provideBoolean')]
    public function testMarkAsResetRunningOnce(bool $firstExecution): void
    {
        $this->subscription->expects($this->once())->method('disclose')->willReturn(ProjectionStatus::RESETTING);
        $this->subscription->expects($this->once())->method('revise');
        $this->subscription->expects($this->never())->method('restart');

        $instance = $this->newInstance();

        $this->assertFalse($instance->recover($firstExecution, false));
    }

    #[DataProvider('provideBoolean')]
    public function testMarkAsResetKeepRunning(bool $firstExecution): void
    {
        $this->subscription->expects($this->once())->method('disclose')->willReturn(ProjectionStatus::RESETTING);
        $this->subscription->expects($this->once())->method('revise');

        ! $firstExecution
            ? $this->subscription->expects($this->once())->method('restart')
            : $this->subscription->expects($this->never())->method('restart');

        $instance = $this->newInstance();

        $this->assertFalse($instance->recover($firstExecution, true));
    }

    #[DataProvider('provideBoolean')]
    public function testMarkAsDeleteWithoutEmittedEvent(bool $firstExecution): void
    {
        $this->subscription->expects($this->exactly(2))->method('disclose')->willReturn(ProjectionStatus::DELETING);
        $this->subscription->expects($this->exactly(2))->method('discard')->with(false);

        $instance = $this->newInstance();

        $this->assertSame($firstExecution, $instance->recover($firstExecution, false));
        $this->assertSame($firstExecution, $instance->recover($firstExecution, true));
    }

    #[DataProvider('provideBoolean')]
    public function testMarkAsDeleteWithEmittedEvent(bool $firstExecution): void
    {
        $this->subscription->expects($this->exactly(2))->method('disclose')->willReturn(ProjectionStatus::DELETING_WITH_EMITTED_EVENTS);
        $this->subscription->expects($this->exactly(2))->method('discard')->with(true);

        $instance = $this->newInstance();

        $this->assertSame($firstExecution, $instance->recover($firstExecution, false));
        $this->assertSame($firstExecution, $instance->recover($firstExecution, true));
    }

    #[DataProvider('provideStatuses')]
    public function testReturnFalseWithNotHandledStatus(ProjectionStatus $status)
    {
        $this->subscription->expects($this->exactly(2))->method('disclose')->willReturn($status);

        $this->assertFalse($this->newInstance()->recover(true, false));
        $this->assertFalse($this->newInstance()->recover(false, false));
    }

    public static function provideStatuses(): Generator
    {
        yield [ProjectionStatus::IDLE];
        yield [ProjectionStatus::RUNNING];
    }

    public static function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }

    private function newInstance(): object
    {
        $subscription = $this->subscription;

        return new class($subscription)
        {
            public function __construct(PersistentSubscriptionInterface $subscription)
            {
                $this->subscription = $subscription;
            }

            use RemoteStatusDiscovery;

            public function recover(bool $isFirstExecution, bool $shouldKeepRunning): bool
            {
                return $this->refreshStatus($isFirstExecution, $shouldKeepRunning);
            }
        };
    }
}
