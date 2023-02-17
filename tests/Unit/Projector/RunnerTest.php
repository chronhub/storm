<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Projector\Scheme\Runner;

final class RunnerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $controller = new Runner();

        $this->assertFalse($controller->isStopped());
        $this->assertFalse($controller->inBackground());
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_assert_stop_running(bool $stop): void
    {
        $controller = new Runner();

        $this->assertFalse($controller->isStopped());

        $controller->stop($stop);

        $this->assertEquals($stop, $controller->isStopped());
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_assert_running_in_background(bool $inBackground): void
    {
        $controller = new Runner();

        $this->assertFalse($controller->inBackground());

        $controller->runInBackground($inBackground);

        $this->assertEquals($inBackground, $controller->inBackground());
    }

    public function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
