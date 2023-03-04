<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Closure;
use DateInterval;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Chronhub\Storm\Projector\Scheme\DetectGap;
use Chronhub\Storm\Projector\Scheme\StreamPosition;

final class DetectGapTest extends UnitTestCase
{
    private StreamPosition|MockObject $streamPosition;

    private PointInTime $clock;

    protected function setUp(): void
    {
        $this->streamPosition = $this->createMock(StreamPosition::class);
        $this->clock = new PointInTime();
    }

    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $gapDetector = new DetectGap(
            $this->streamPosition,
            $this->clock,
            [0, 5, 10],
            'PT60S'
        );

        $this->assertFalse($gapDetector->hasGap());
        $this->assertRetries($gapDetector, 0);
    }

    /**
     * @test
     */
    public function it_does_not_detect_gap_when_retries_is_an_empty_array(): void
    {
        $this->streamPosition->expects($this->never())->method('hasNextPosition');

        $gapDetector = new DetectGap($this->streamPosition, $this->clock, [], 'PT60S');

        $eventTime = $this->clock->now()->format($this->clock::DATE_TIME_FORMAT);

        $this->assertFalse($gapDetector->detect('customer', 10, $eventTime));
        $this->assertFalse($gapDetector->hasGap());
    }

    /**
     * @test
     */
    public function it_does_not_detect_gap_when_next_position_is_not_available_with_detection_window(): void
    {
        $eventTime = $this->clock
            ->now()
            ->sub(new DateInterval('PT1S'))
            ->format($this->clock::DATE_TIME_FORMAT);

        $this->streamPosition->expects($this->once())
            ->method('hasNextPosition')
            ->with('customer', 3)
            ->willReturn(true);

        $gapDetector = new DetectGap($this->streamPosition, $this->clock, [5, 10, 20], 'PT60S');

        $this->assertFalse($gapDetector->detect('customer', 3, $eventTime));
        $this->assertFalse($gapDetector->hasGap());
    }

    /**
     * @test
     */
    public function it_does_not_detect_gap_when_no_more_retries(): void
    {
        $eventTime = $this->clock
            ->now()
            ->sub(new DateInterval('PT1S'))
            ->format($this->clock::DATE_TIME_FORMAT);

        $this->streamPosition->expects($this->any())
            ->method('hasNextPosition')
            ->with('customer', 2)
            ->willReturn(false);

        $gapDetector = new DetectGap($this->streamPosition, $this->clock, [5, 10, 20], null);

        $this->assertRetries($gapDetector, 0);
        $this->assertTrue($gapDetector->detect('customer', 2, $eventTime));

        $gapDetector->sleep();
        $this->assertRetries($gapDetector, 1);
        $this->assertTrue($gapDetector->detect('customer', 2, $eventTime));

        $gapDetector->sleep();
        $this->assertRetries($gapDetector, 2);
        $this->assertTrue($gapDetector->detect('customer', 2, $eventTime));

        $gapDetector->sleep();
        $this->assertRetries($gapDetector, 3);

        $this->assertFalse($gapDetector->detect('customer', 2, $eventTime));
    }

    /**
     * @test
     */
    public function it_does_not_detect_gap_when_event_time_is_greater_than_detection_window_from_now(): void
    {
        $this->streamPosition
            ->expects($this->once())
            ->method('hasNextPosition')
            ->with('customer', 4);

        $gapDetector = new DetectGap($this->streamPosition, $this->clock, ['5'], 'PT60S');

        $gapDetector->detect('customer', 4, $this->clock->now()->format($this->clock::DATE_TIME_FORMAT));

        $this->assertFalse($gapDetector->hasGap());
    }

    /**
     * @test
     */
    public function it_detect_gap(): void
    {
        $eventTime = $this->clock
            ->now()
            ->sub(new DateInterval('PT1S'))
            ->format($this->clock::DATE_TIME_FORMAT);

        $this->streamPosition->expects($this->any())
            ->method('hasNextPosition')
            ->with('customer', 3)
            ->willReturn(false);

        $gapDetector = new DetectGap($this->streamPosition, $this->clock, [5, 10, 20], null);

        $this->assertTrue($gapDetector->detect('customer', 3, $eventTime));
        $this->assertTrue($gapDetector->hasGap());
    }

    /**
     * @test
     */
    public function it_reset_retries_and_return_silently_when_no_more_retries_available(): void
    {
        $gapDetector = new DetectGap($this->streamPosition, $this->clock, [5, 10, 20], 'PT60S');

        $gapDetector->sleep();
        $gapDetector->sleep();
        $gapDetector->sleep();
        $gapDetector->sleep();

        $this->assertRetries($gapDetector, 3);
    }

    /**
     * @test
     */
    public function it_reset_gap_detected(): void
    {
        $this->streamPosition->expects($this->any())
            ->method('hasNextPosition')
            ->with('customer', 3)
            ->willReturn(false);

        $gapDetector = new DetectGap($this->streamPosition, $this->clock, [5, 10, 20], null);

        $eventTime = $this->clock->now()->format($this->clock->getFormat());

        $this->assertTrue($gapDetector->detect('customer', 3, $eventTime));
        $this->assertTrue($gapDetector->hasGap());

        $gapDetector->resetGap();

        $this->assertFalse($gapDetector->hasGap());
    }

    private function assertRetries(DetectGap $instance, int $expectedRetries): void
    {
        $closure = Closure::bind(static fn ($instance) => $instance->retries, null, DetectGap::class);

        $this->assertEquals($expectedRetries, $closure($instance));
    }
}
