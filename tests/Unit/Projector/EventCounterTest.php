<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Projector\Scheme\EventCounter;

final class EventCounterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $counter = new EventCounter(10);

        $this->assertEquals(0, $counter->current());

        $this->assertTrue($counter->isReset());
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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
