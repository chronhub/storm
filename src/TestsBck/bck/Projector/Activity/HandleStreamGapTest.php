<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Projector\Activity\HandleStreamGap;
use Chronhub\Storm\Projector\Scheme\StreamGapManager;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(HandleStreamGap::class)]
final class HandleStreamGapTest extends UnitTestCase
{
    private PersistentSubscriptionInterface|MockObject $subscription;

    private StreamGapManager|MockObject $gapDetector;

    protected function setUp(): void
    {
        $this->subscription = $this->createMock(PersistentSubscriptionInterface::class);
        $this->gapDetector = $this->createMock(StreamGapManager::class);
    }

    public function testItDoesNotDetectStreamGap(): void
    {
        $this->subscription->expects($this->never())->method('store');
        $this->subscription->expects($this->once())->method('gap')->willReturn($this->gapDetector);
        $this->gapDetector->expects($this->once())->method('hasGap')->willReturn(false);

        $handleStreamGap = new HandleStreamGap();

        $next = static fn ($subscription) => true;

        $handleStreamGap($this->subscription, $next);

        $this->assertTrue(true);
    }

    public function testItDetectStreamGap(): void
    {
        $this->subscription->expects($this->once())->method('store');
        $this->subscription->expects($this->exactly(2))->method('gap')->willReturn($this->gapDetector);
        $this->gapDetector->expects($this->once())->method('hasGap')->willReturn(true);
        $this->gapDetector->expects($this->once())->method('sleep');

        $handleStreamGap = new HandleStreamGap();

        $next = static fn ($subscription) => true;

        $handleStreamGap($this->subscription, $next);

        $this->assertTrue(true);
    }
}
