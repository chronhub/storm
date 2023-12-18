<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Provider\EventStreamLoader;
use Chronhub\Storm\Projector\Stream\Checkpoint;

beforeEach(function (): void {
    $this->clock = $this->createMock(SystemClock::class);
    $this->eventStreamLoader = $this->createMock(EventStreamLoader::class);
});

test('new instance', function () {
    $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1], null);

    expect($streamManager->retriesInMs)
        ->toBe([1])
        ->and($streamManager->detectionWindows)->toBeNull()
        ->and($streamManager->hasGap())->toBeFalse()
        ->and($streamManager->retries())->toBe(0)
        ->and($streamManager->all())->toBeEmpty()
        ->and($streamManager->jsonSerialize())->toBe([]);
});

test('watch streams', function () {
    $this->eventStreamLoader->expects($this->once())
        ->method('loadFrom')
        ->with(['all' => true])
        ->willReturn(collect(['customer']));

    $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1], null);
    $streamManager->discover(['all' => true]);

    expect($streamManager->all())->toBe(['customer' => 0]);
});

test('watch streams with added event stream', function () {
    $this->eventStreamLoader->expects($this->exactly(2))
        ->method('loadFrom')
        ->with(['names' => ['foo', 'bar']])
        ->willReturnOnConsecutiveCalls(collect(['foo']), collect(['foo', 'bar']));

    $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1], null);
    $streamManager->discover(['names' => ['foo', 'bar']]);

    expect($streamManager->all())->toBe(['foo' => 0]);

    $streamManager->discover(['names' => ['foo', 'bar']]);

    expect($streamManager->all())->toBe(['foo' => 0, 'bar' => 0]);
});

test('watch streams with unrecoverable event stream', function () {
    $this->eventStreamLoader->expects($this->exactly(2))
        ->method('loadFrom')
        ->with(['names' => ['foo', 'bar']])
        ->willReturnOnConsecutiveCalls(collect(['foo', 'bar']), collect(['bar']));

    $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1], null);
    $streamManager->discover(['names' => ['foo', 'bar']]);

    expect($streamManager->all())->toBe(['foo' => 0, 'bar' => 0]);

    $streamManager->discover(['names' => ['foo', 'bar']]);

    expect($streamManager->all())->toBe(['bar' => 0, 'foo' => 0]);
});

test('sync streams', function () {
    $this->eventStreamLoader->expects($this->once())
        ->method('loadFrom')->with(['all' => true])->willReturn(collect(['customer']));

    $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1], null);
    $streamManager->discover(['all' => true]);

    expect($streamManager->all())->toBe(['customer' => 0]);

    $streamManager->sync(['customer' => 10]);

    expect($streamManager->all())->toBe(['customer' => 10]);
});

test('bind raises exception when stream name is not currently watched', function () {
    $this->clock->expects($this->never())->method('isNowSubGreaterThan');
    $this->eventStreamLoader->expects($this->never())->method('loadFrom');

    $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1], null);

    expect($streamManager->all())->toBe([]);

    $streamManager->bind('customer', 10, 'event time');
})->throws(RuntimeException::class, 'Stream customer is not watched');

describe('always bind stream to its position', function () {

    test('when event time is false which is meant for query projection', function (int $position) {
        $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1], null);
        $streamManager->sync(['customer' => 10]);

        expect($streamManager->bind('customer', $position, false))->toBe(true)
            ->and($streamManager->all())->toBe(['customer' => $position]);
    })->with([
        'next position available' => [11],
        'next position with gap' => [12, 14],
        'overwrite position' => [10],
    ]);

    test('when no retry setup', function (int $position) {
        $this->clock->expects($this->never())->method('isNowSubGreaterThan');

        $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [], null);

        $streamManager->sync(['customer' => 10]);

        $bound = $streamManager->bind('customer', $position, 'event_time');

        expect($bound)
            ->toBe(true)
            ->and($streamManager->all())->toBe(['customer' => $position]);
    })->with([
        'next position available' => [11],
        'next position with gap' => [12, 14],
        'overwrite position' => [10],
    ]);
});

test('bind stream to the next position available', function (int $currentPosition) {
    $this->clock->expects($this->never())->method('isNowSubGreaterThan');

    $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1, 2], null);
    $streamManager->sync(['customer' => $currentPosition]);

    $bound = $streamManager->bind('customer', $currentPosition + 1, 'event_time');

    expect($bound)
        ->toBe(true)
        ->and($streamManager->all())->toBe(['customer' => $currentPosition + 1]);
})->with(['current position' => [10, 120, 1548]]);

test('bind stream when gap is detected but detection windows bypassed', function (int $currentPosition) {
    $this->clock->expects($this->once())->method('isNowSubGreaterThan')->willReturn(true);

    $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1, 2], 'PT1D');

    $streamManager->sync(['customer' => $currentPosition]);

    $bound = $streamManager->bind('customer', $currentPosition + 2, 'event_time');

    expect($bound)
        ->toBe(false)
        ->and($streamManager->all())->toBe(['customer' => $currentPosition]);
})->with(['current position' => [10, 120, 1548]]);

test('detect gap', function (int $currentPosition) {
    $this->clock->expects($this->once())->method('isNowSubGreaterThan')->willReturn(true);

    $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1, 2], 'PT1D');

    $streamManager->sync(['customer' => $currentPosition]);

    expect($streamManager->bind('customer', $currentPosition + 2, 'event_time'))->toBe(false)
        ->and($streamManager->all())->toBe(['customer' => $currentPosition])
        ->and($streamManager->retries())->toBe(0)
        ->and($streamManager->hasRetry())->toBeTrue()
        ->and($streamManager->hasGap())->toBeTrue();
})->with(['current position' => [10, 120, 1548]]);

test('bind stream when gap is detected but no more retry left', function (int $currentPosition) {
    $this->clock->expects($this->exactly(1))->method('isNowSubGreaterThan')->willReturn(true);

    $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1, 2], 'PT1D');
    $streamManager->sync(['customer' => $currentPosition]);

    expect($streamManager->bind('customer', $currentPosition + 2, 'event_time'))->toBe(false)
        ->and($streamManager->all())->toBe(['customer' => $currentPosition])
        ->and($streamManager->retries())->toBe(0)
        ->and($streamManager->hasRetry())->toBeTrue()
        ->and($streamManager->hasGap())->toBeTrue();

    $streamManager->sleep();
    $streamManager->sleep();

    expect($streamManager->retries())->toBe(2)
        ->and($streamManager->hasRetry())->toBeFalse()
        ->and($streamManager->hasGap())->toBeTrue()
        ->and($streamManager->bind('customer', $currentPosition + 2, 'event_time'))->toBe(true);
})->with(['current position' => [10, 120, 1548]]);

test('resets manager', function () {
    $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1, 2], 'PT1D');

    $streamManager->sync(['customer' => 10]);

    expect($streamManager->all())->toBe(['customer' => 10]);

    $streamManager->resets();

    expect($streamManager->all())->toBe([]);
});

test('reset manager with detected gap and retries', function () {
    $this->clock->expects($this->once())->method('isNowSubGreaterThan')->willReturn(true);

    $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1, 2], 'PT1D');

    $streamManager->sync(['customer' => 10]);

    expect($streamManager->bind('customer', 10 + 2, 'event_time'))->toBe(false)
        ->and($streamManager->all())->toBe(['customer' => 10])
        ->and($streamManager->retries())->toBe(0)
        ->and($streamManager->hasRetry())->toBeTrue()
        ->and($streamManager->hasGap())->toBeTrue();

    $streamManager->resets();

    expect($streamManager->all())->toBe([])
        ->and($streamManager->retries())->toBe(0)
        ->and($streamManager->hasRetry())->toBeTrue()
        ->and($streamManager->hasGap())->toBeFalse();
});

test('sleep raised exception when no gap detected', function () {
    $this->clock->expects($this->never())->method('isNowSubGreaterThan');
    $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1, 2]);

    $streamManager->sync(['customer' => 10]);

    expect($streamManager->bind('customer', 10 + 1, 'event_time'))->toBe(true)
        ->and($streamManager->all())->toBe(['customer' => 11])
        ->and($streamManager->retries())->toBe(0)
        ->and($streamManager->hasRetry())->toBeTrue()
        ->and($streamManager->hasGap())->toBeFalse();

    $streamManager->sleep();
})->throws(RuntimeException::class, 'No gap detected');

test('sleep raised exception when gap detected but no more retry left', function () {
    $this->clock->expects($this->never())->method('isNowSubGreaterThan');
    $streamManager = new Checkpoint($this->eventStreamLoader, $this->clock, [1, 2, 3, 4]);

    $streamManager->sync(['customer' => 10]);

    expect($streamManager->bind('customer', 10 + 2, 'event_time'))->toBe(false)
        ->and($streamManager->all())->toBe(['customer' => 10])
        ->and($streamManager->retries())->toBe(0)
        ->and($streamManager->hasRetry())->toBeTrue()
        ->and($streamManager->hasGap())->toBeTrue();

    while ($streamManager->hasRetry()) {
        $streamManager->sleep();
    }

    $streamManager->sleep();
})->throws(RuntimeException::class, 'No more retries');
