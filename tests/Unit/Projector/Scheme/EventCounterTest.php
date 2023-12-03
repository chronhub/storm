<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Scheme\EventCounter;

test('new instance', function (): void {
    $counter = new EventCounter(3);

    expect($counter->current())->toBe(0)
        ->and($counter->limit)->toBe(3)
        ->and($counter->isReset())->toBeTrue()
        ->and($counter->isReached())->toBeFalse();
});

it('raise exception when limit is less than one', function (int $limit): void {
    new EventCounter($limit);
})
    ->with([[0, -1, -10]])
    ->throws(InvalidArgumentException::class, 'Event counter limit must be greater than');

it('can increment counter', function (): void {
    $limit = 3;
    $count = 0;

    $counter = new EventCounter($limit);
    expect($counter->current())->toBe(0);

    while ($limit > 0) {
        $counter->increment();
        $limit--;
        $count++;

        expect($counter->current())->toBe($count);
    }
});

it('can increment counter beyond the limit', function (): void {
    $limit = 3;
    $counter = new EventCounter(3);

    while ($limit > 0) {
        $counter->increment();
        $limit--;
    }

    expect($counter->current())->toBe(3);

    $counter->increment();
    expect($counter->current())->toBe(4);
});

it('can reset counter', function (): void {
    $limit = 3;
    $counter = new EventCounter(3);

    while ($limit > 0) {
        $counter->increment();
        expect($counter->isReset())->toBeFalse();

        $limit--;
    }

    $counter->reset();
    expect($counter->current())->toBe(0)
        ->and($counter->isReset())->toBeTrue()
        ->and($counter->isReached())->toBeFalse();
});

it('return true when limit is reached', function (): void {
    $limit = 3;
    $counter = new EventCounter($limit);

    while ($counter->current() < $limit) {
        $counter->increment();
        expect($counter->isReached())->toBeFalse();

        $limit--;
    }

    $counter->increment();
    expect($counter->isReached())->toBeTrue();
});

it('return true when incremented counter is greater than limit', function (): void {
    $limit = 3;
    $counter = new EventCounter($limit);

    while ($counter->current() < $limit) {
        $counter->increment();
        expect($counter->isReached())->toBeFalse();

        $limit--;
    }

    $counter->increment();
    expect($counter->isReached())->toBeTrue();

    $counter->increment();
    expect($counter->isReached())->toBeTrue();
});
