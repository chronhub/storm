<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Clock;

use DateTimeZone;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use function usleep;
use function microtime;
use function date_default_timezone_get;

#[CoversClass(PointInTime::class)]
final class PointInTimeTest extends UnitTestCase
{
    #[Test]
    public function it_can_be_constructed_with_string_timezone(): void
    {
        $clock = new PointInTime('UTC');

        $this->assertSame('UTC', $clock->now()->getTimezone()->getName());
    }

    #[Test]
    public function it_can_be_constructed_with_timezone_instance(): void
    {
        $clock = new PointInTime(new DateTimeZone('UTC'));

        $this->assertSame('UTC', $clock->now()->getTimezone()->getName());
    }

    #[Test]
    public function it_can_be_constructed(): void
    {
        $clock = new PointInTime();
        $this->assertSame('UTC', $clock->now()->getTimezone()->getName());

        $timezone = date_default_timezone_get();
        $clock = new PointInTime();
        $this->assertSame($timezone, $clock->now()->getTimezone()->getName());
    }

    #[Test]
    public function it_change_timezone(): void
    {
        $clock = new PointInTime();
        $this->assertSame('UTC', $clock->now()->getTimezone()->getName());

        $timezone = date_default_timezone_get();
        $clock = new PointInTime();
        $this->assertSame($timezone, $clock->now()->getTimezone()->getName());

        $newClock = $clock->withTimeZone('Europe/Paris');
        $this->assertNotSame($clock, $newClock);

        $this->assertSame('Europe/Paris', $newClock->now()->getTimezone()->getName());
    }

    #[Test]
    public function it_test_now(): void
    {
        $clock = new PointInTime();

        $before = microtime(true);

        usleep(10);

        $now = $clock->now();

        usleep(10);

        $after = microtime(true);

        $this->assertGreaterThan($before, (float) $now->format('U.u'));
        $this->assertLessThan($after, (float) $now->format('U.u'));
    }

    #[Test]
    public function it_test_sleep(): void
    {
        $clock = new PointInTime();
        $timezone = $clock->now()->getTimezone()->getName();

        $before = microtime(true);

        $clock->sleep(1.5);
        $now = (float) $clock->now()->format('U.u');

        usleep(10);

        $after = microtime(true);

        $this->assertGreaterThanOrEqual($before + 1.499999, $now);
        $this->assertLessThan($after, $now);
        $this->assertLessThan(1.9, $now - $before);
        $this->assertSame($timezone, $clock->now()->getTimezone()->getName());
    }

    #[Test]
    public function it_can_be_serialized_from_format(): void
    {
        $clock = new PointInTime();

        $this->assertEquals($clock::DATE_TIME_FORMAT, $clock->getFormat());
        $this->assertEquals('Y-m-d\TH:i:s.u', $clock->getFormat());

        $now = $clock->now();

        $stringClock = $now->format($clock->getFormat());

        $this->assertEquals(
            $stringClock,
            $now::createFromFormat($clock->getFormat(), $stringClock)->format($clock->getFormat())
        );
    }
}
