<?php

declare(strict_types=1);

namespace Chronhub\Storm\Clock;

use DateTimeImmutable;
use Illuminate\Support\Collection;

class PointInTimeFactory
{
    public static function make(): DateTimeImmutable
    {
        return self::getInstance()->now();
    }

    public static function times(int $times = 1): Collection
    {
        return Collection::times($times, fn (): DateTimeImmutable => self::make());
    }

    /**
     * @return Collection<DateTimeImmutable>
     */
    public static function timesWithInterval(string $startModifier, string $intervalModifier = '+1 second', int $counter = 1): Collection
    {
        $now = self::make();

        $collection = new Collection();
        $start = $now->modify($startModifier);

        while ($counter > 0) {
            $start = $start->modify($intervalModifier);
            $counter--;

            $collection->push($start);
        }

        return $collection;
    }

    public static function between(
        string $startModifier,
        string $endModifier,
        string $intervalModifier = '+1 second',
        int $expectedCounter = 2,
    ): Collection {
        $now = self::make();

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
