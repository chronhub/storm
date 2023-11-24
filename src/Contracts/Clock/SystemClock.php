<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Clock;

use DateInterval;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

interface SystemClock extends ClockInterface
{
    public function toString(): string;

    public function toPointInTime(DateTimeImmutable|string $pointInTime): DateTimeImmutable;

    public function sleep(float|int $seconds): void;

    public function format(DateTimeImmutable|string $pointInTime): string;

    /**
     * Compare datetime greater than given point in time.
     */
    public function isGreaterThan(DateTimeImmutable|string $pointInTime, DateTimeImmutable|string $anotherPointInTime): bool;

    /**
     * Compare now datetime greater than given point in time.
     */
    public function isGreaterThanNow(DateTimeImmutable|string $pointInTime): bool;

    /**
     * Compare now datetime subtracted by interval with given point in time.
     */
    public function isNowSubGreaterThan(DateInterval|string $interval, DateTimeImmutable|string $pointInTime): bool;

    public function getFormat(): string;
}
