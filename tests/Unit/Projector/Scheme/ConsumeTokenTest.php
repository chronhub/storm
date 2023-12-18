<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Projector\Support\Token\ConsumeToken;
use InvalidArgumentException;

use function microtime;
use function range;
use function sleep;
use function usleep;

it('initialize with the correct parameters', function () {
    $tokenBucket = new ConsumeToken(10, 5);

    expect($tokenBucket->capacity)->toBe(10.0)
        ->and($tokenBucket->rate)->toBe(5.0)
        ->and($tokenBucket->getCapacity())->toBe(10.0)
        ->and($tokenBucket->getRate())->toBe(5.0);
});

it('can consume tokens within the rate limit', function () {
    $tokenBucket = new ConsumeToken(1, 5);

    expect($tokenBucket->consume())->toBeTrue()
        ->and($tokenBucket->consume())->toBeFalse()
        ->and($tokenBucket->consume())->toBeFalse()
        ->and($tokenBucket->consume())->toBeFalse()
        ->and($tokenBucket->consume())->toBeFalse()
        ->and($tokenBucket->consume())->toBeFalse('Sixth token: not enough time has passed');

    sleep(1);

    expect($tokenBucket->consume())->toBeTrue('sixth token: after refill');
});

it('rejects token consumption beyond the rate limit', function () {
    $tokenBucket = new ConsumeToken(1, 2);

    expect($tokenBucket->consume())->toBeTrue()
        ->and($tokenBucket->consume())->toBeFalse('Second token: beyond limit');
});

it('refills tokens after a certain period', function () {
    $tokenBucket = new ConsumeToken(1, 1);

    expect($tokenBucket->consume())->toBeTrue()
        ->and($tokenBucket->consume())->toBeFalse('Second token: not enough time has passed');

    sleep(1);

    expect($tokenBucket->consume())->toBeTrue('Second token: after refill');
});

it('dynamically adjusts sleep duration between token consumption attempts', function () {
    $tokenBucket = new ConsumeToken(1, 2);

    expect($tokenBucket->consume())->toBeTrue();

    $start = microtime(true);
    usleep(500000);

    expect($tokenBucket->consume())->toBeTrue()
        ->and($tokenBucket->consume())->toBeFalse();

    $elapsedTime = (microtime(true) - $start);

    expect($elapsedTime)->toBeGreaterThanOrEqual(0.5)
        ->and($tokenBucket->consume())->toBeFalse('Third token: not enough time has passed');
});

it('rejects token consumption beyond the capacity', function () {
    $tokenBucket = new ConsumeToken(5, 1);

    foreach (range(1, 5) as $i) {
        expect($tokenBucket->consume())->toBeTrue("Token $i");
    }

    expect($tokenBucket->consume())->toBeFalse('Sixth token: beyond capacity');
});

it('consumes tokens at expected intervals with sleep periods', function () {
    $tokenBucket = new ConsumeToken(1, 2);

    expect($tokenBucket->consume())->toBeTrue();

    sleep(1);

    expect($tokenBucket->consume())->toBeTrue('Second token: after sleep');
});

it('raises an exception when capacity and rate is less than zero', function (int $capacity, int $rate) {
    new ConsumeToken($capacity, $rate);
})
    ->with([
        'zero capacity' => [0, 1],
        'zero rate' => [1, 0],
    ])
    ->throws(InvalidArgumentException::class, 'Capacity and rate must be greater than zero');
