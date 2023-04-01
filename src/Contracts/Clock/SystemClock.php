<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Clock;

use DateInterval;
use DateTimeImmutable;
use Symfony\Component\Clock\ClockInterface;

interface SystemClock extends ClockInterface
{
    public function toDateTimeImmutable(string|DateTimeImmutable $pointInTime): DateTimeImmutable;

    public function format(string|DateTimeImmutable $pointInTime): string;

    public function getFormat(): string;

    /**
     * Compare now datetime greater than given point in time.
     */
    public function isGreaterThan(string|DateTimeImmutable $pointInTime, string|DateTimeImmutable $anotherPointInTime): bool;

    /**
     * Compare now datetime greater than given point in time.
     */
    public function isGreaterThanNow(string|DateTimeImmutable $pointInTime): bool;

    /**
     * Compare now datetime subtracted by interval with given point in time.
     */
    public function isNowSubGreaterThan(string|DateInterval $interval, string|DateTimeImmutable $pointInTime): bool;
}
