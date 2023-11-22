<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Scheme\EventStreamLoader;
use Chronhub\Storm\Projector\Scheme\StreamManager;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;

class StreamManagerTest extends UnitTestCase
{
    private SystemClock|MockObject $clock;

    private EventStreamProvider|MockObject $eventStreamProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clock = $this->createMock(SystemClock::class);
        $this->eventStreamProvider = $this->createMock(EventStreamProvider::class);
    }

    /**
     * @test
     */
    public function testInstance(): void
    {
        $streamManager = $this->newStreamManager();

        $this->assertEquals([], $streamManager->jsonSerialize());
        $this->assertEquals([], $streamManager->confirmedGaps());
        $this->assertFalse($streamManager->hasGap());
        $this->assertEquals(0, $streamManager->retries());
    }

    /**
     * @test
     */
    public function testWatchAll(): void
    {
        $streamManager = $this->newStreamManager();

        $this->eventStreamProvider->expects($this->once())->method('allWithoutInternal')->willReturn(['foo']);

        $streamManager->watchStreams(['all' => true]);

        $this->assertEquals(['foo' => 0], $streamManager->jsonSerialize());
    }

    /**
     * @test
     */
    public function testExceptionRaisedWhenNoStreamReturnFromWatchAll(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No streams found');

        $this->eventStreamProvider->expects($this->once())->method('allWithoutInternal')->willReturn([]);

        $streamManager = $this->newStreamManager();

        $streamManager->watchStreams(['all' => true]);
    }

    /**
     * @test
     */
    public function testWatchCategories(): void
    {
        $streamManager = $this->newStreamManager();

        $this->eventStreamProvider->expects($this->once())->method('filterByAscendantCategories')->willReturn(['foo']);

        $streamManager->watchStreams(['categories' => ['foo']]);

        $this->assertEquals(['foo' => 0], $streamManager->jsonSerialize());
    }

    /**
     * @test
     */
    public function testExceptionRaisedWhenNoStreamReturnFromWatchCategories(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No streams found');

        $this->eventStreamProvider->expects($this->once())->method('filterByAscendantCategories')->willReturn([]);

        $streamManager = $this->newStreamManager();

        $streamManager->watchStreams(['categories' => ['foo']]);
    }

    /**
     * @test
     */
    public function testWatchStreams(): void
    {
        $streamManager = $this->newStreamManager();

        $streamManager->watchStreams(['names' => ['foo']]);

        $this->assertEquals(['foo' => 0], $streamManager->jsonSerialize());
    }

    /**
     * @test
     */
    public function testExceptionRaisedWhenNoStreamProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stream names can not be empty');

        $streamManager = $this->newStreamManager();

        $streamManager->watchStreams(['names' => []]);
    }

    /**
     * @test
     */
    public function testDiscoverStreams(): void
    {
        $streamManager = $this->newStreamManager();

        $streamManager->watchStreams(['names' => ['foo']]);

        $streamManager->syncStreams(['bar' => 2]);

        $this->assertEquals(['foo' => 0, 'bar' => 2], $streamManager->jsonSerialize());
    }

    /**
     * @test
     */
    public function testOverwriteDiscoverStreamsToLocal(): void
    {
        $streamManager = $this->newStreamManager();

        $streamManager->watchStreams(['names' => ['foo']]);

        $this->assertEquals(['foo' => 0], $streamManager->jsonSerialize());

        $streamManager->syncStreams(['foo' => 5, 'bar' => 2]);

        $this->assertEquals(['foo' => 5, 'bar' => 2], $streamManager->jsonSerialize());
    }

    /**
     * @test
     */
    public function testNeverDetectGapWhenEventTimeIsFalse(): void
    {
        $streamManager = $this->newStreamManager();

        $streamManager->watchStreams(['names' => ['foo']]);

        $this->assertTrue($streamManager->bind('foo', 5, false));
        $this->assertFalse($streamManager->hasGap());
    }

    /**
     * @test
     *
     * @dataProvider provideRetries
     */
    public function testDetectGapWhenFullFilledRetries(array $retriesInMs, int $expectedRetries): void
    {
        // Arrange
        $streamManager = $this->newStreamManager($retriesInMs);
        $streamManager->watchStreams(['names' => ['foo']]);
        $this->assertFalse($streamManager->bind('foo', 5, 'event_time'));

        // Act
        $countRetries = 0;
        while ($streamManager->hasRetry()) {
            $streamManager->sleep();
            $countRetries++;
        }

        // Assert
        $this->assertEquals($expectedRetries, $countRetries);
        $this->assertTrue($streamManager->hasGap());

        $this->assertTrue($streamManager->bind('foo', 5, 'event_time'));
        $this->assertFalse($streamManager->hasGap());
    }

    /**
     * @test
     */
    public function testExceptionWhenNoGapDetectedOnSleep(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No gap detected');

        $retriesInMs = [1, 2, 3];

        $streamManager = $this->newStreamManager($retriesInMs);

        $streamManager->watchStreams(['names' => ['foo']]);

        while ($streamManager->hasRetry()) {
            $streamManager->sleep();
        }

        $streamManager->sleep();
    }

    /**
     * @test
     */
    public function testExceptionWhenRetriesExhaustedOnSleep(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No more retries');

        $retriesInMs = [1, 2, 3];

        $streamManager = $this->newStreamManager($retriesInMs);

        $streamManager->watchStreams(['names' => ['foo']]);
        $this->assertFalse($streamManager->bind('foo', 5, 'event_time'));

        while ($streamManager->hasRetry()) {
            $streamManager->sleep();
        }

        $streamManager->sleep();
    }

    /**
     * @test
     */
    public function testBindStreamNameToPosition(): void
    {
        $streamManager = $this->newStreamManager();

        $streamManager->watchStreams(['names' => ['foo']]);

        $this->assertEquals(['foo' => 0], $streamManager->jsonSerialize());

        $this->assertTrue($streamManager->bind('foo', 1, 'event_time'));

        $this->assertEquals(['foo' => 1], $streamManager->jsonSerialize());
    }

    /**
     * @test
     */
    public function testExceptionRaisedWhenBindNotFoundStreamName(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Stream foo not watched');

        $streamManager = $this->newStreamManager();

        $this->assertEquals([], $streamManager->jsonSerialize());

        $streamManager->bind('foo', 5, 'event_time');
    }

    /**
     * @test
     */
    public function testResets(): void
    {
        $streamManager = $this->newStreamManager([1]);

        // bind stream
        $streamManager->watchStreams(['names' => ['foo']]);
        $this->assertEquals(['foo' => 0], $streamManager->jsonSerialize());
        $this->assertTrue($streamManager->bind('foo', 1, 'event_time'));
        $this->assertEquals(['foo' => 1], $streamManager->jsonSerialize());

        // add gap
        $this->assertFalse($streamManager->bind('foo', 3, 'event_time'));
        $this->assertSame(0, $streamManager->retries());
        $this->assertTrue($streamManager->hasRetry());
        $this->assertTrue($streamManager->hasGap());
        $this->assertEquals([], $streamManager->confirmedGaps());

        $streamManager->sleep();

        // retries exhausted
        $this->assertSame(1, $streamManager->retries());
        $this->assertFalse($streamManager->hasRetry());

        // add gap
        $this->assertTrue($streamManager->bind('foo', 3, 'event_time'));

        $this->assertSame(0, $streamManager->retries());
        $this->assertTrue($streamManager->hasRetry());
        $this->assertFalse($streamManager->hasGap());
        $this->assertEquals([3], $streamManager->confirmedGaps());

        // reset
        $streamManager->resets();

        $this->assertEquals([], $streamManager->jsonSerialize());
        $this->assertEquals([], $streamManager->confirmedGaps());
        $this->assertFalse($streamManager->hasGap());
        $this->assertEquals(0, $streamManager->retries());
        $this->assertTrue($streamManager->hasRetry());
    }

    /**
     * @test
     */
    public function testGapWithPastDetectionWindows(): void
    {
        $streamManager = $this->newStreamManager([1], 'some_interval');

        $this->clock->expects($this->exactly(3))->method('isNowSubGreaterThan')->with('some_interval', 'event_time')->willReturn(false);

        // bind stream
        $streamManager->watchStreams(['names' => ['foo']]);

        $this->assertTrue($streamManager->bind('foo', 1, 'event_time'));
        $this->assertTrue($streamManager->bind('foo', 3, 'event_time'));

        try {
            $streamManager->sleep();
        } catch (LogicException $e) {
            $this->assertSame('No gap detected', $e->getMessage());
        }

        $this->assertTrue($streamManager->hasRetry());
        $this->assertTrue($streamManager->bind('foo', 5, 'event_time'));
        $this->assertTrue($streamManager->bind('foo', 7, 'event_time'));
    }

    /**
     * @test
     */
    public function testGapWithFutureDetectionWindows(): void
    {
        $streamManager = $this->newStreamManager([1], 'some_interval');

        $this->clock->expects($this->once())->method('isNowSubGreaterThan')->with('some_interval', 'event_time')->willReturn(true);

        // bind stream
        $streamManager->watchStreams(['names' => ['foo']]);

        $this->assertTrue($streamManager->bind('foo', 1, 'event_time'));
        $this->assertFalse($streamManager->bind('foo', 3, 'event_time'));

        try {
            $streamManager->sleep();
        } catch (LogicException $e) {
            $this->assertSame('No gap detected', $e->getMessage());
        }

        $this->assertFalse($streamManager->hasRetry());
        $this->assertTrue($streamManager->hasGap());
    }

    /**
     * @test
     */
    public function testGapWithoutRetries(): void
    {
        $streamManager = $this->newStreamManager([], 'some_interval');

        $this->clock->expects($this->never())->method('isNowSubGreaterThan');

        // bind streams
        $streamManager->watchStreams(['names' => ['foo']]);
        $this->assertTrue($streamManager->bind('foo', 5, 'event_time'));
        $this->assertSame(['foo' => 5], $streamManager->jsonSerialize());

        $this->assertTrue($streamManager->bind('foo', 7, 'event_time'));
        $this->assertSame(['foo' => 7], $streamManager->jsonSerialize());

        $this->assertSame(0, $streamManager->retries());
        $this->assertFalse($streamManager->hasRetry());
        $this->assertFalse($streamManager->hasGap());
        $this->assertSame([], $streamManager->confirmedGaps());
    }

    public static function provideRetries(): Generator
    {
        yield from [
            [[1, 2, 3], 3],
            [[1, 2, 3, 4], 4],
            [[1, 2, 3, 4, 5], 5],
        ];
    }

    private function newStreamManager(array $retriesInMs = [], string $detectionWindows = null): StreamManager
    {
        return new StreamManager(
            new EventStreamLoader($this->eventStreamProvider),
            $this->clock,
            $retriesInMs,
            $detectionWindows
        );
    }
}
