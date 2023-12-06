<?php

declare(strict_types=1);

namespace Chronhub\Storm\Clock;

use const STR_PAD_LEFT;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use RuntimeException;
use Throwable;

use function explode;
use function hrtime;
use function is_string;
use function microtime;
use function preg_match;
use function sleep;
use function str_pad;
use function strlen;
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
    final public const PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}$/';

    private DateTimeZone $timezone;

    private int $sOffset;

    private int $usOffset;

    public function __construct()
    {
        $this->initializeOffsets();

        $this->timezone = new DateTimeZone('UTC');
    }

    public function now(): DateTimeImmutable
    {
        [$s, $us] = hrtime();

        if (1000000 <= $us = (int) ($us / 1000) + $this->usOffset) {
            $s++;
            $us -= 1000000;
        } elseif ($us < 0) {
            $s--;
            $us += 1000000;
        }

        if (strlen($now = (string) $us) !== 6) {
            $now = str_pad($now, 6, '0', STR_PAD_LEFT);
        }

        $now = '@'.($s + $this->sOffset).'.'.$now;

        return (new DateTimeImmutable($now, $this->timezone))->setTimezone($this->timezone);
    }

    public function toString(): string
    {
        return $this->now()->format(self::DATE_TIME_FORMAT);
    }

    public function isGreaterThan(DateTimeImmutable|string $pointInTime, DateTimeImmutable|string $anotherPointInTime): bool
    {
        return $this->toPointInTime($pointInTime) > $this->toPointInTime($anotherPointInTime);
    }

    public function isGreaterThanNow(DateTimeImmutable|string $pointInTime): bool
    {
        return $this->now() < $this->toPointInTime($pointInTime);
    }

    public function isNowSubGreaterThan(DateInterval|string $interval, DateTimeImmutable|string $pointInTime): bool
    {
        if (is_string($interval)) {
            $interval = new DateInterval(strtoupper($interval));
        }

        return $this->now()->sub($interval) > $this->toPointInTime($pointInTime);
    }

    public function toPointInTime(DateTimeImmutable|string $pointInTime): DateTimeImmutable
    {
        if ($pointInTime instanceof DateTimeImmutable) {
            if ($pointInTime->getTimezone()->getName() !== $this->timezone->getName()) {
                throw new DomainException('Point in time must be in UTC timezone');
            }

            $pointInTime = $pointInTime->format(self::DATE_TIME_FORMAT);

        }

        if (! preg_match(self::PATTERN, $pointInTime)) {
            throw new DomainException("Point in time given has an invalid format: $pointInTime");
        }

        try {
            return (new DateTimeImmutable($pointInTime, $this->timezone))->setTimezone($this->timezone);
        } catch (Throwable $e) {
            throw new DomainException("Invalid point in time given: $pointInTime", 0, $e);
        }
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

    public function format(DateTimeImmutable|string $pointInTime): string
    {
        return $this->toPointInTime($pointInTime)->format(self::DATE_TIME_FORMAT);
    }

    public function getFormat(): string
    {
        return self::DATE_TIME_FORMAT;
    }

    private function initializeOffsets(): void
    {
        $offset = hrtime();

        if ($offset === false) {
            throw new RuntimeException('hrtime() returned false: the runtime environment does not provide access to a monotonic timer.');
        }

        $time = explode(' ', microtime(), 2);
        $this->sOffset = $time[1] - $offset[0];
        $this->usOffset = (int) ($time[0] * 1000000) - (int) ($offset[1] / 1000);
    }
}
