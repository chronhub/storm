<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

final class EventCounterTest extends UnitTestCase
{
    public function testIncrementCounter(): void
    {
        $counter = new EventCounter(3);
        $this->assertEquals(0, $counter->current());

        $counter->increment();
        $this->assertEquals(1, $counter->current());

        $counter->increment();
        $this->assertEquals(2, $counter->current());

        $counter->increment();
        $this->assertEquals(3, $counter->current());
    }

    public function testResetCounter(): void
    {
        $counter = new EventCounter(3);

        $counter->increment();
        $counter->increment();
        $counter->increment();

        $this->assertFalse($counter->isReset());

        $counter->reset();
        $this->assertTrue($counter->isReset());
        $this->assertEquals(0, $counter->current());
    }

    public function testIsReachedReturnsTrueWhenLimitReached(): void
    {
        $counter = new EventCounter(3);

        $this->assertFalse($counter->isReached());

        $counter->increment();
        $this->assertFalse($counter->isReached());

        $counter->increment();
        $this->assertFalse($counter->isReached());

        $counter->increment();
        $this->assertTrue($counter->isReached());
    }

    public function testExceptionRaisedWhenLimitIsLessThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be greater than 0');

        new EventCounter(0);
    }
}
