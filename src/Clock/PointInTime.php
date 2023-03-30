<?php

declare(strict_types=1);

namespace Chronhub\Storm\Clock;

use DateInterval;
use DateTimeZone;
use DateTimeImmutable;
use Symfony\Component\Clock\MonotonicClock;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use function sleep;
use function usleep;
use function is_string;
use function strtoupper;

final class PointInTime implements SystemClock
{
    final public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.u';

    private DateTimeZone $timezone;

    public function __construct(null|string|DateTimeZone $timezone = null)
    {
        if ($timezone === null) {
            $this->timezone = new DateTimeZone('UTC');
        } else {
            $this->timezone = is_string($timezone) ? new DateTimeZone($timezone) : $timezone;
        }
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

    public function withTimeZone(DateTimeZone|string $timezone): static
    {
        $clone = clone $this;

        $clone->timezone = is_string($timezone) ? new DateTimeZone($timezone) : $timezone;

        return $clone;
    }

    public function getFormat(): string
    {
        return self::DATE_TIME_FORMAT;
    }

    private function toDateTimeImmutable(DateTimeImmutable|string $pointInTime): DateTimeImmutable
    {
        if ($pointInTime instanceof DateTimeImmutable) {
            return $pointInTime;
        }

        return DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, $pointInTime, $this->timezone);
    }
}
