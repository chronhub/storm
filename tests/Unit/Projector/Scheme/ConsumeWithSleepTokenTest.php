<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Support\Token\ConsumeWithSleepToken;

use function microtime;
use function usleep;

it('can consume token and sleep after each consume', function () {
    $tokenBucket = new ConsumeWithSleepToken(1, 1);

    expect($tokenBucket->consume())->toBeTrue()
        ->and($tokenBucket->consume())->toBeTrue()
        ->and($tokenBucket->consume())->toBeTrue();
});

it('can consume one token every ten seconds with given capacity of one and float rate of 0.1', function () {
    $tokenBucket = new ConsumeWithSleepToken(1, 0.1);

    $start = microtime(true);
    expect($tokenBucket->consume())->toBeTrue()
        ->and($tokenBucket->consume())->toBeTrue();

    $elapsedTime = (microtime(true) - $start);

    expect($elapsedTime)->toBeGreaterThanOrEqual(10.0);
})->group('sleep');

it('can consume ten token every second with given capacity of one and rate of 10', function () {
    $tokenBucket = new ConsumeWithSleepToken(1, 10);

    $start = microtime(true);

    $count = 0;
    while (microtime(true) - $start <= 1) {
        $count++;
        expect($tokenBucket->consume())->toBeTrue();
        usleep(100000);
    }

    expect($count)->toBe(10);
})->group('sleep');

it('raise exception when given token exceeds capacity of the bucket', function () {
    $tokenBucket = new ConsumeWithSleepToken(1, 1);

    $tokenBucket->consume(2);
})
    ->throws(InvalidArgumentException::class, 'Requested tokens exceed the capacity of the token bucket.')
    ->group('sleep');

it('dynamically adjusts sleep duration between token consumption attempts', function () {
    $tokenBucket = new ConsumeWithSleepToken(1, 2);

    expect($tokenBucket->consume())->toBeTrue();

    $start = microtime(true);
    usleep(500000);

    expect($tokenBucket->consume())->toBeTrue()
        ->and($tokenBucket->consume())->toBeTrue();

    $elapsedTime = (microtime(true) - $start);

    expect($elapsedTime)->toBeGreaterThanOrEqual(0.5)
        ->and($tokenBucket->consume())->toBeTrue()
        ->and($tokenBucket->consume())->toBeTrue();
})->group('sleep');
