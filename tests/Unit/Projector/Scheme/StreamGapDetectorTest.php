<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Scheme\StreamGapManager;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

use function microtime;

#[CoversClass(StreamGapManager::class)]
final class StreamGapDetectorTest extends UnitTestCase
{
    private StreamPosition $streamPosition;

    private SystemClock|MockObject $clock;

    private function getGapDetector(array $retriesInMs, ?string $detectionWindows): StreamGapManager
    {
        return new StreamGapManager($this->streamPosition, $this->clock, $retriesInMs, $detectionWindows);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->streamPosition = new StreamPosition($this->createMock(EventStreamProvider::class));
        $this->clock = $this->createMock(SystemClock::class);
    }

    public function testDetectShouldReturnFalseWhenRetriesInMsIsEmpty(): void
    {
        $retriesInMs = [];
        $detectionWindows = null;

        $detector = $this->getGapDetector($retriesInMs, $detectionWindows);

        $result = $detector->detect('streamName', 1, '2022-01-01');

        $this->assertFalse($result);
        $this->assertSame(0, $detector->retries());
    }

    public function testDetectShouldReturnFalseWhenGapIsNotDetected(): void
    {
        $retriesInMs = [1000];
        $detectionWindows = null;

        $detector = $this->getGapDetector($retriesInMs, $detectionWindows);

        $result = $detector->detect('streamName', 1, '2022-01-01');

        $this->assertFalse($result);
        $this->assertFalse($detector->hasGap());
        $this->assertSame(0, $detector->retries());
    }

    public function testDetectShouldReturnFalseWhenDetectionWindowIsNotElapsed(): void
    {
        $this->clock->expects($this->once())->method('isNowSubGreaterThan')->willReturn(false);

        $retriesInMs = [1000];
        $detectionWindows = 'PT1S';

        $detector = $this->getGapDetector($retriesInMs, $detectionWindows);

        $result = $detector->detect('streamName', 10, '2022-01-01');

        $this->assertFalse($result);
        $this->assertFalse($detector->hasGap());
        $this->assertSame(0, $detector->retries());
    }

    public function testDetectShouldReturnTrueAndSetGapDetectedWhenGapIsDetected(): void
    {
        $this->clock->method('isNowSubGreaterThan')->willReturn(true);

        $retriesInMs = [1000];
        $detectionWindows = 'PT1S';

        $detector = $this->getGapDetector($retriesInMs, $detectionWindows);

        $result = $detector->detect('streamName', 10, '2022-01-01');

        $this->assertTrue($result);
        $this->assertTrue($detector->hasGap());
        $this->assertSame(0, $detector->retries());
    }

    public function testSleepTillRetries(): void
    {
        $retriesInMs = [1000, 2000, 3000];
        $detectionWindows = null;

        $detector = $this->getGapDetector($retriesInMs, $detectionWindows);

        $startTime = microtime(true);

        $detector->sleep();
        $detector->sleep();
        $detector->sleep();

        $this->assertSame(3, $detector->retries());

        $endTime = microtime(true);

        $elapsedTimeInMs = ($endTime - $startTime) * 100000;

        // Assert that the elapsed time is within an acceptable range
        $this->assertGreaterThanOrEqual(600, $elapsedTimeInMs);
        $this->assertLessThanOrEqual(800, $elapsedTimeInMs);
    }

    public function testNoSleepWhenNoMoreRetries(): void
    {
        $retriesInMs = [1000, 2000, 3000];
        $detectionWindows = null;

        $detector = $this->getGapDetector($retriesInMs, $detectionWindows);

        $startTime = microtime(true);

        $detector->sleep();
        $detector->sleep();
        $detector->sleep();

        $this->assertSame(3, $detector->retries());

        // No more retries
        $detector->sleep();
        $detector->sleep();
        $detector->sleep();

        $this->assertSame(3, $detector->retries());

        $endTime = microtime(true);

        $elapsedTimeInMs = ($endTime - $startTime) * 100000;

        // Assert that the elapsed time is within an acceptable range
        $this->assertGreaterThanOrEqual(600, $elapsedTimeInMs);
        $this->assertLessThanOrEqual(900, $elapsedTimeInMs);
    }
}
