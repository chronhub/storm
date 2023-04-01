<?php

declare(strict_types=1);

namespace Chronhub\Storm\Clock;

use DateInterval;
use DateTimeZone;
use DomainException;
use DateTimeImmutable;
use Symfony\Component\Clock\MonotonicClock;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use function sleep;
use function usleep;
use function is_string;
use function strtoupper;

final readonly class PointInTime implements SystemClock
{
    final public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.u';

    private DateTimeZone $timezone;

    public function __construct()
    {
        $this->timezone = new DateTimeZone('UTC');
    }

    public function now(): DateTimeImmutable
    {
        return (new MonotonicClock($this->timezone))->now();
    }

    public function isGreaterThan(DateTimeImmutable|string $pointInTime, DateTimeImmutable|string $anotherPointInTime): bool
    {
        return $this->toDateTimeImmutable($pointInTime) > $this->toDateTimeImmutable($anotherPointInTime);
    }

    public function isGreaterThanNow(string|DateTimeImmutable $pointInTime): bool
    {
        return $this->now() < $this->toDateTimeImmutable($pointInTime);
    }

    public function isNowSubGreaterThan(string|DateInterval $interval, string|DateTimeImmutable $pointInTime): bool
    {
        if (is_string($interval)) {
            $interval = new DateInterval(strtoupper($interval));
        }

        return $this->now()->sub($interval) > $this->toDateTimeImmutable($pointInTime);
    }

    public function sleep(float|int $seconds): void
    {
        if (0 < $s = (int) $seconds) {
            sleep($s);
        }

        if (0 < $us = $seconds - $s) {
            usleep((int) ($us * 1E6));
        }
    }

    public function toDateTimeImmutable(string|DateTimeImmutable $pointInTime): DateTimeImmutable
    {
        if ($pointInTime instanceof DateTimeImmutable) {
            $pointInTime = $pointInTime->format(self::DATE_TIME_FORMAT);
        }

        return DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, $pointInTime, $this->timezone);
    }

    public function format(string|DateTimeImmutable $pointInTime): string
    {
        return $this->toDateTimeImmutable($pointInTime)->format(self::DATE_TIME_FORMAT);
    }

    public function getFormat(): string
    {
        return self::DATE_TIME_FORMAT;
    }

    public function withTimeZone(DateTimeZone|string $timezone): static
    {
        throw new DomainException('Point in time use immutable UTC date time zone by default');
    }
}
