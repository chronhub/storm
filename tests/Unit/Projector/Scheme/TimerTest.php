<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Clock\PointInTime;
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

        $this->assertTrue($timer->isNotElapsed());

        $this->clock->sleep(2);

        $this->assertFalse($timer->isNotElapsed());
    }

    public function testTimeNeverElapsed(): void
    {
        $timer = new Timer($this->clock, null);

        $timer->start();

        $this->assertTrue($timer->isNotElapsed());

        $this->clock->sleep(2);

        $this->assertTrue($timer->isNotElapsed());

        $startedAt = ReflectionProperty::getProperty($timer, 'now');

        $this->assertNull($startedAt);
    }
}
