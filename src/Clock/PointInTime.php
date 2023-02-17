<?php

declare(strict_types=1);

namespace Chronhub\Storm\Clock;

use DateTimeZone;
use DateTimeImmutable;
use Symfony\Component\Clock\MonotonicClock;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use function sleep;
use function usleep;
use function is_string;

final class PointInTime implements SystemClock
{
    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.u';

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
}
