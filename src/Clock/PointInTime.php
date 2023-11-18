<?php

declare(strict_types=1);

namespace Chronhub\Storm\Clock;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Exception;
use Symfony\Component\Clock\MonotonicClock;

use function is_string;
use function preg_match;
use function sleep;
use function strtoupper;
use function usleep;

final readonly class PointInTime implements SystemClock
{
    /**
     * @var string
     */
    final public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.u';

    /**
     * @var string
     */
    final public const PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}$/';

    private DateTimeZone $timezone;

    public function __construct()
    {
        $this->timezone = new DateTimeZone('UTC');
    }

    public function now(): DateTimeImmutable
    {
        return (new MonotonicClock($this->timezone))->now();
    }

    public function nowToString(): string
    {
        return $this->now()->format(self::DATE_TIME_FORMAT);
    }

    public function isGreaterThan(DateTimeImmutable|string $pointInTime, DateTimeImmutable|string $anotherPointInTime): bool
    {
        return $this->toDateTimeImmutable($pointInTime) > $this->toDateTimeImmutable($anotherPointInTime);
    }

    public function isGreaterThanNow(DateTimeImmutable|string $pointInTime): bool
    {
        return $this->now() < $this->toDateTimeImmutable($pointInTime);
    }

    public function isNowSubGreaterThan(DateInterval|string $interval, DateTimeImmutable|string $pointInTime): bool
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
            return $pointInTime;
        }

        if (! preg_match(self::PATTERN, $pointInTime)) {
            throw new DomainException("Point in time given has an invalid format: $pointInTime");
        }

        try {
            return new DateTimeImmutable($pointInTime, $this->timezone);
        } catch (Exception $e) {
            throw new DomainException("Invalid point in time given: $pointInTime", 0, $e);
        }
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
