<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Units\Projector\Repository;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Clock\PointInTimeFactory;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Repository\LockManager;
use DateInterval;
use DateTimeImmutable;

beforeEach(function () {
    $this->clock = $this->createMock(SystemClock::class);
    $this->time = new PointInTime();
});

dataset('dateTimes', [
    'now' => PointInTimeFactory::now(),
    'now plus 10 secs' => PointInTimeFactory::now()->add(new DateInterval('PT10S')),
    'now plus one hour' => PointInTimeFactory::now()->add(new DateInterval('PT1H')),
]);

test('lock manager instance', function (int $timeout, int $threshold): void {
    $instance = new LockManager($this->clock, $timeout, $threshold);

    expect($instance->lockTimeout)->toBe($timeout)
        ->and($instance->lockThreshold)->toBe($threshold);
})
    ->with(['timeout' => fn () => 1000, 2000, 3000])
    ->with(['threshold' => fn () => 0, 5000, 10000, 200000]);

test('current method of new instance raise exception when lock has not been acquired', function (): void {
    $instance = new LockManager($this->clock, 1000, 5000);

    $instance->current();
})->throws(RuntimeException::class, 'Lock is not acquired');

test('acquire lock', function (DateTimeImmutable $currentTime): void {
    $this->clock->expects($this->once())->method('now')->willReturn($currentTime);
    $this->clock->expects($this->once())->method('getFormat')->willReturn($this->time::DATE_TIME_FORMAT);

    $instance = new LockManager($this->clock, 1000, 5000);

    expect($instance->acquire())->toBe($currentTime->format($this->time::DATE_TIME_FORMAT));
})->with('dateTimes');

describe('should refresh', function () {

    test('when lock is not acquired', function () {
        $instance = new LockManager($this->clock, 2000, 5000);

        try {
            $instance->current();
        } catch (RuntimeException $e) {
            expect($e->getMessage())->toBe('Lock is not acquired');
        }

        expect($instance->shouldRefresh())->toBeTrue();
    });

    test('when lock threshold is set to zero', function () {
        $now = $this->time->now();

        $this->clock->expects($this->once())->method('now')->willReturn($now);

        $instance = new LockManager($this->clock, 1000, 0);
        $instance->acquire();

        expect($instance->shouldRefresh())->toBeTrue();
    });

    test('with given time', function () {
        //        $instance = new LockManager($this->time, 1000, 5000);
        //        $currentLock = $instance->acquire();
        //
        //        expect($instance->shouldRefresh())->toBeFalse()
        //            ->and($currentLock)->toBe($instance->current());
        //
        //        $future = $this->time->now()->add(new DateInterval('PT6S'));
        //
        //        expect($instance->shouldRefresh())->toBeTrue();
    })->todo();
});

test('refresh lock', function () {
    //    $now = $this->time->now();
    //    $future = $now->add(new DateInterval('PT6S'));
    //
    //    $this->clock->expects($this->exactly(3))->method('now')
    //        ->willReturnOnConsecutiveCalls($now, $now, $future);
    //
    //    $this->clock->expects($this->exactly(3))
    //        ->method('getFormat')->willReturn($this->time::DATE_TIME_FORMAT);
    //
    //    $instance = new LockManager($this->clock, 1000, 5000);
    //
    //    $currentLock = $instance->acquire();
    //
    //    expect($instance->shouldRefresh())->toBeFalse()
    //        ->and($currentLock)->toBe($instance->current());
    //
    //    //fake lock with future on third call
    //    $refreshLock = $instance->refresh();
    //
    //    expect($this->time->toPointInTime($currentLock)->modify('+1'.$instance->lockTimeout.' milliseconds'))
    //        ->toBeLessThan($this->time->toPointInTime($refreshLock));
})->todo();
