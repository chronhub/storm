<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Pipes;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\Scheme\EventCounter;

#[CoversClass(EventCounter::class)]
final class EventCounterTest extends UnitTestCase
{
    #[Test]
    public function it_can_be_constructed(): void
    {
        $counter = new EventCounter(10);

        $this->assertEquals(0, $counter->current());

        $this->assertTrue($counter->isReset());
    }

    #[Test]
    public function it_can_be_incremented(): void
    {
        $counter = new EventCounter(5);

        $counter->increment();
        $this->assertEquals(1, $counter->current());

        $counter->increment();
        $this->assertEquals(2, $counter->current());

        $this->assertFalse($counter->isReached());

        $counter->increment();
        $counter->increment();
        $counter->increment();

        $this->assertTrue($counter->isReached());
    }

    #[Test]
    public function it_can_be_reset(): void
    {
        $counter = new EventCounter(10);

        $counter->increment();

        $this->assertEquals(1, $counter->current());

        $counter->increment();

        $this->assertEquals(2, $counter->current());

        $counter->reset();

        $this->assertTrue($counter->isReset());
        $this->assertEquals(0, $counter->current());
    }
}
