<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Iterator;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Reporter\DomainEvent;
use DateTimeImmutable;
use DateTimeZone;
use Iterator;
use function min;

/**
 * Dummy version of Prooph TimSort.
 *
 * @see https://github.com/prooph/event-store/blob/ff213c26c366af90a57d369595734b6ca202f687/src/StreamIterator/TimSort.php
 */
trait HasTimSort
{
    private int $timSortRun = 32;

    /**
     * this function sorts array from left index to right index which is of size at most $timSortRun.
     */
    private function insertionSort(array &$arr, int $left, int $right): void
    {
        for ($i = $left + 1; $i <= $right; $i++) {
            $temp = $arr[$i];
            $j = $i - 1;
            while ($j >= $left && $this->greaterThan($arr[$j][0], $temp[0])) {
                $arr[$j + 1] = $arr[$j];
                $j--;
            }
            $arr[$j + 1] = $temp;
        }
    }

    /**
     * Iterative Tim sort function to sort the array[0...n-1] (similar to merge sort).
     */
    private function timSort(array &$arr, int $n): void
    {
        // Sort individual sub arrays of size RUN
        for ($i = 0; $i < $n; $i += $this->timSortRun) {
            $this->insertionSort($arr, $i, min($i + 31, $n - 1));
        }
    }

    private function greaterThan(Iterator $a, Iterator $b): bool
    {
        $aValid = $a->valid();
        $bValid = $b->valid();

        if (! $aValid || ! $bValid) {
            return true === $bValid;
        }

        return $this->toDateTime($a->current()) > $this->toDateTime($b->current());
    }

    private function toDateTime(DomainEvent $message): DateTimeImmutable
    {
        $eventTime = $message->header(Header::EVENT_TIME);

        if ($eventTime instanceof DateTimeImmutable) {
            return $eventTime;
        }

        return new DateTimeImmutable($eventTime, new DateTimeZone('UTC'));
    }
}
