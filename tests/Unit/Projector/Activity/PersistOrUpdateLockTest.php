<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Projector\Activity\PersistOrUpdateLock;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamGapDetector;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(PersistOrUpdateLock::class)]
final class PersistOrUpdateLockTest extends UnitTestCase
{
    private PersistentSubscriptionInterface|MockObject $subscription;

    private StreamGapDetector|MockObject $gapDetector;

    private EventCounter $eventCounter;

    private ProjectionOption|MockObject $option;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscription = $this->createMock(PersistentSubscriptionInterface::class);
        $this->gapDetector = $this->createMock(StreamGapDetector::class);
        $this->option = $this->createMock(ProjectionOption::class);
        $this->eventCounter = new EventCounter(5);
    }

    public function testItHasGapAndKeepToNextActivity(): void
    {
        $this->subscription->expects($this->never())->method('store');
        $this->subscription->expects($this->never())->method('renew');
        $this->subscription->expects($this->once())->method('gap')->willReturn($this->gapDetector);
        $this->gapDetector->expects($this->once())->method('hasGap')->willReturn(true);
        $this->subscription->expects($this->never())->method('eventCounter');
        $this->subscription->expects($this->never())->method('option');
        $this->option->expects($this->never())->method('getSleep');

        $persistOrUpdateLock = new PersistOrUpdateLock();

        $next = static fn ($subscription) => true;

        $persistOrUpdateLock($this->subscription, $next);

        $this->assertTrue(true);
    }

    public function testNoGapAndCounterIsReset(): void
    {
        $this->subscription->expects($this->never())->method('store');
        $this->subscription->expects($this->once())->method('renew');
        $this->subscription->expects($this->once())->method('gap')->willReturn($this->gapDetector);
        $this->gapDetector->expects($this->once())->method('hasGap')->willReturn(false);
        $this->subscription->expects($this->once())->method('eventCounter')->willReturn($this->eventCounter);
        $this->subscription->expects($this->once())->method('option')->willReturn($this->option);
        $this->option->expects($this->once())->method('getSleep')->willReturn(1000);

        $this->assertTrue($this->eventCounter->isReset());

        $persistOrUpdateLock = new PersistOrUpdateLock();

        $next = static fn ($subscription) => true;

        $persistOrUpdateLock($this->subscription, $next);

        $this->assertTrue(true);
    }

    public function testNoGapAndCounterIsNotReset(): void
    {
        $this->subscription->expects($this->once())->method('store');
        $this->subscription->expects($this->never())->method('renew');
        $this->subscription->expects($this->once())->method('gap')->willReturn($this->gapDetector);
        $this->gapDetector->expects($this->once())->method('hasGap')->willReturn(false);
        $this->subscription->expects($this->once())->method('eventCounter')->willReturn($this->eventCounter);
        $this->subscription->expects($this->never())->method('option');
        $this->option->expects($this->never())->method('getSleep');

        $this->eventCounter->increment();
        $this->assertFalse($this->eventCounter->isReset());

        $persistOrUpdateLock = new PersistOrUpdateLock();

        $next = static fn ($subscription) => true;

        $persistOrUpdateLock($this->subscription, $next);

        $this->assertTrue(true);
    }
}
