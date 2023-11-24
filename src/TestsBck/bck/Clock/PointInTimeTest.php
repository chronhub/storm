<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Clock;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Tests\UnitTestCase;
use DateInterval;
use DomainException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;

use function date_default_timezone_get;
use function microtime;
use function usleep;

#[CoversClass(PointInTime::class)]
final class PointInTimeTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $clock = new PointInTime();
        $this->assertSame('UTC', $clock->now()->getTimezone()->getName());

        $timezone = date_default_timezone_get();
        $clock = new PointInTime();
        $this->assertSame($timezone, $clock->now()->getTimezone()->getName());
    }

    public function testUpdateTimezone(): void
    {
        $this->expectException(DomainException::class);

        $clock = new PointInTime();

        $clock->withTimeZone('Europe/Paris');
    }

    public function testCurrentTime(): void
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

    public function testSleep(): void
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

    public function testPointInTimeIsGreaterThanAnotherTime(): void
    {
        $clock = new PointInTime();

        $isGreater = $clock->isGreaterThan($clock->now(), $clock->now()->sub(new DateInterval('PT1S')));

        $this->assertTrue($isGreater);
    }

    public function testGivenPointInTimeIsGreaterThanNow(): void
    {
        $clock = new PointInTime();

        $isGreater = $clock->isGreaterThanNow($clock->now()->add(new DateInterval('PT1S')));

        $this->assertTrue($isGreater);
    }

    public function testGivenPointInTimeIsLessThanNow(): void
    {
        $clock = new PointInTime();

        $isGreater = $clock->isGreaterThanNow($clock->now()->sub(new DateInterval('PT1S')));

        $this->assertFalse($isGreater);
    }

    public function testGivenIntervalSubWithNowIsGreaterThanGivenTime(): void
    {
        $clock = new PointInTime();

        $isGreater = $clock->isNowSubGreaterThan('PT1S', $clock->now()->sub(new DateInterval('PT2S')));

        $this->assertTrue($isGreater);
    }

    public function testToDateTimeImmutable(): void
    {
        $clock = new PointInTime();
        $now = $clock->now();

        $toDatetime = $clock->toPointInTime($now);

        $this->assertEquals($now, $toDatetime);
        $this->assertSame($now, $toDatetime);
    }

    public function testToDateTimeImmutableFromString(): void
    {
        $clock = new PointInTime();
        $now = $clock->now();

        $toDatetime = $clock->toPointInTime($now->format($clock->getFormat()));

        $this->assertEquals($now, $toDatetime);
    }

    public function testExceptionRaisedWithInvalidDatetime(): void
    {
        $clock = new PointInTime();

        try {
            $clock->toPointInTime('invalid date time');
        } catch (DomainException $e) {
            $this->assertSame('Invalid point in time format: invalid date time', $e->getMessage());
            $this->assertInstanceOf(Exception::class, $e->getPrevious());
            $this->assertSame('Failed to parse time string (invalid date time) at position 0 (i): The timezone could not be found in the database', $e->getPrevious()->getMessage());
        }
    }

    public function testFormatGivenTime(): void
    {
        $clock = new PointInTime();
        $nowString = $clock->now()->format($clock::DATE_TIME_FORMAT);

        $toDatetime = $clock->toPointInTime($nowString);

        $this->assertSame($nowString, $clock->format($toDatetime));
        $this->assertSame($nowString, $clock->format($toDatetime->format($clock::DATE_TIME_FORMAT)));
    }

    public function testItSerializeDatetimeWithGivenToFormat(): void
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
