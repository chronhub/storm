<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Projector\Scheme\Runner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Runner::class)]
final class RunnerTest extends UnitTestCase
{
    #[Test]
    public function it_can_be_constructed(): void
    {
        $controller = new Runner();

        $this->assertFalse($controller->isStopped());
        $this->assertFalse($controller->inBackground());
    }

    #[Test]
    #[DataProvider('provideBoolean')]
    public function it_assert_stop_running(bool $stop): void
    {
        $controller = new Runner();

        $this->assertFalse($controller->isStopped());

        $controller->stop($stop);

        $this->assertEquals($stop, $controller->isStopped());
    }

    #[Test]
    #[DataProvider('provideBoolean')]
    public function it_assert_running_in_background(bool $inBackground): void
    {
        $controller = new Runner();

        $this->assertFalse($controller->inBackground());

        $controller->runInBackground($inBackground);

        $this->assertEquals($inBackground, $controller->inBackground());
    }

    public static function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
