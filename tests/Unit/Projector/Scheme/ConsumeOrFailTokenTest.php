<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Projector\Exceptions\NoMoreTockenBucketAvailable;
use Chronhub\Storm\Projector\Support\Token\ConsumeOrFailToken;

use function sleep;

it('can consume token', function () {
    $tokenBucket = new ConsumeOrFailToken(1, 1);

    expect($tokenBucket->consume())->toBeTrue();
});

it('can not consume token', function () {
    $tokenBucket = new ConsumeOrFailToken(1, 1);

    expect($tokenBucket->consume())->toBeTrue();

    $tokenBucket->consume();
})->throws(NoMoreTockenBucketAvailable::class);

it('can consume token with sleeping time', function () {
    $tokenBucket = new ConsumeOrFailToken(1, 1);

    expect($tokenBucket->consume())->toBeTrue();

    sleep(1);

    expect($tokenBucket->consume())->toBeTrue();
});
