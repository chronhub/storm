<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Units\Projector\Repository;

use Chronhub\Storm\Clock\PointInTime;
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
    'now' => getPointInTime()->now(),
    'now plus 10 secs' => getPointInTime()->now()->add(new DateInterval('PT10S')),
    'now plus one hour' => getPointInTime()->now()->add(new DateInterval('PT1H')),
]);

test('lock manager instance', function (int $timeout, int $threshold): void {
    $instance = new LockManager($this->clock, $timeout, $threshold);

    expect($instance->lockTimeout)->toBe($timeout)
        ->and($instance->lockThreshold)->toBe($threshold);
})
    ->with(['timeout' => fn () => 1000, 2000, 3000])
    ->with(['threshold' => fn () => 0, 5000, 10000, 200000]);

test('current of new instance raise exception', function (): void {
    $instance = new LockManager($this->clock, 1000, 5000);

    $instance->current();
})->throws(RuntimeException::class, 'Lock is not acquired');

test('acquire lock', function (DateTimeImmutable $currentTime): void {
    $this->clock->expects($this->once())->method('now')->willReturn($currentTime);
    $this->clock->expects($this->once())->method('getFormat')->willReturn($this->time::DATE_TIME_FORMAT);

    $instance = new LockManager($this->clock, 1000, 5000);

    $updated = $currentTime->modify('+1000 milliseconds');

    expect($instance->acquire())->toBe($updated->format($this->time::DATE_TIME_FORMAT));
})->with('dateTimes');

describe('should refresh', function () {
    test('when lock is not acquired', function (DateTimeImmutable $datetime) {
        $instance = new LockManager($this->clock, 2000, 5000);

        try {
            $instance->current();
        } catch (RuntimeException $e) {
            expect($e->getMessage())->toBe('Lock is not acquired');
        }

        expect($instance->shouldRefresh($datetime))->toBeTrue();
    })->with('dateTimes');

    test('when lock threshold is set to zero', function () {
        $now = $this->time->now();

        $this->clock->expects($this->once())->method('now')->willReturn($now);

        $instance = new LockManager($this->clock, 1000, 0);
        $instance->acquire();

        expect($instance->shouldRefresh($this->time->now()))->toBeTrue()
            ->and($instance->shouldRefresh($this->time->now()))->toBeTrue()
            ->and($instance->shouldRefresh($this->time->now()))->toBeTrue();
    });

    test('with given time', function () {
        $instance = new LockManager($this->time, 1000, 5000);
        $currentLock = $instance->acquire();

        expect($instance->shouldRefresh($this->time->now()))->toBeFalse()
            ->and($currentLock)->toBe($instance->current());

        $future = $this->time->now()->add(new DateInterval('PT6S'));

        expect($instance->shouldRefresh($future))->toBeTrue();
    });
});

test('refresh with given time', function () {
    $instance = new LockManager($this->time, 1000, 5000);

    $currentLock = $instance->acquire();

    $currentTime = $this->time->now()->add(new DateInterval('PT1S'));

    $refreshLock = $instance->refresh($currentTime);

    $expectedTime = $this->time->toPointInTime($currentTime)->add(new DateInterval('PT1S'))->format($this->time::DATE_TIME_FORMAT);

    expect($refreshLock)->not()->toBe($currentLock)
        ->and($instance->current())->toBe($refreshLock)
        ->and($instance->current())->toBe($expectedTime);
});
