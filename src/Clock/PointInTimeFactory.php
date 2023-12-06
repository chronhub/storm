<?php

declare(strict_types=1);

namespace Chronhub\Storm\Clock;

use DateTimeImmutable;
use Illuminate\Support\Collection;

class PointInTimeFactory
{
    public static function now(): DateTimeImmutable
    {
        return self::getInstance()->now();
    }

    public static function nowToString(): string
    {
        return self::now()->format(PointInTime::DATE_TIME_FORMAT);
    }

    public static function toString(DateTimeImmutable $pointInTime): string
    {
        return self::getInstance()->toPointInTime($pointInTime)->format(PointInTime::DATE_TIME_FORMAT);
    }

    public static function times(int $times = 1): Collection
    {
        return Collection::times($times, fn (): DateTimeImmutable => self::now());
    }

    /**
     * @return Collection<DateTimeImmutable>
     */
    public static function timesWithInterval(
        string $startModifier,
        string $intervalModifier = '+1 second',
        int $counter = 1
    ): Collection {
        $collection = new Collection();
        $now = self::now();
        $start = $now->modify($startModifier);

        while ($counter > 0) {
            $start = $start->modify($intervalModifier);

            $collection->push($start);

            $counter--;
        }

        return $collection;
    }

    /**
     * Depends on the interval modifier, the expected count can be less than expected
     *
     * @return Collection<DateTimeImmutable>
     */
    public static function timesBetween(
        string $startModifier,
        string $endModifier,
        string $intervalModifier = '+1 second',
        int $expectedCounter = 2,
    ): Collection {
        $now = self::now();

        $start = $now->modify($startModifier);
        $end = $now->modify($endModifier);

        $collection = new Collection();

        while ($start < $end && $expectedCounter > 0) {
            $start = $start->modify($intervalModifier);
            $expectedCounter--;

            $collection->push($start);
        }

        return $collection;
    }

    private static function getInstance(): PointInTime
    {
        return new PointInTime();
    }
}
