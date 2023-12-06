<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Scheme\Timer;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tests\Util\ReflectionProperty;
use DateInterval;

final class TimerTest extends UnitTestCase
{
    private PointInTime $clock;

    protected function setUp(): void
    {
        $this->clock = new PointInTime();
    }

    public function testElapseTime(): void
    {
        $timer = new Timer($this->clock, new DateInterval('PT1S'));

        $timer->start();

        $this->assertFalse($timer->isElapsed());

        $this->clock->sleep(2);

        $this->assertTrue($timer->isElapsed());
    }

    public function testTimeNeverElapsedWithNullInterval(): void
    {
        $timer = new Timer($this->clock, null);

        $timer->start();

        $this->assertFalse($timer->isElapsed());

        $this->clock->sleep(2);

        $this->assertFalse($timer->isElapsed());

        $startedAt = ReflectionProperty::getProperty($timer, 'now');

        $this->assertNull($startedAt);
    }

    public function testExceptionRaisedWhenTimerAlreadyStarted(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Timer already started');

        $timer = new Timer($this->clock, new DateInterval('PT1S'));

        $timer->start();
        $timer->start();
    }

    public function testExceptionNotRaisedWhenTimerAlreadyStartedWithNullInterval(): void
    {
        $timer = new Timer($this->clock, null);

        $timer->start();
        $timer->start();

        $this->assertFalse($timer->isElapsed());
    }
}
