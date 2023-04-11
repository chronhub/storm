<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use Chronhub\Storm\Projector\Activity\HandleStreamGap;
use Chronhub\Storm\Projector\Scheme\StreamGapDetector;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class HandleStreamGapTest extends UnitTestCase
{
    private ProjectionManagement|MockObject $repository;

    private PersistentSubscriptionInterface|MockObject $subscription;

    private StreamGapDetector|MockObject $gapDetector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ProjectionManagement::class);
        $this->subscription = $this->createMock(PersistentSubscriptionInterface::class);
        $this->gapDetector = $this->createMock(StreamGapDetector::class);
    }

    public function testItDoesNotDetectStreamGap(): void
    {
        $this->repository->expects($this->never())->method('store');
        $this->subscription->expects($this->once())->method('gap')->willReturn($this->gapDetector);
        $this->gapDetector->expects($this->once())->method('hasGap')->willReturn(false);

        $handleStreamGap = new HandleStreamGap();

        $next = fn ($subscription) => true;

        $handleStreamGap($this->subscription, $this->repository, $next);

        $this->assertTrue(true);
    }

    public function testItDetectStreamGap(): void
    {
        $this->repository->expects($this->once())->method('store');
        $this->subscription->expects($this->exactly(2))->method('gap')->willReturn($this->gapDetector);
        $this->gapDetector->expects($this->once())->method('hasGap')->willReturn(true);
        $this->gapDetector->expects($this->once())->method('sleep');

        $handleStreamGap = new HandleStreamGap();

        $next = fn ($subscription) => true;

        $handleStreamGap($this->subscription, $this->repository, $next);

        $this->assertTrue(true);
    }
}
