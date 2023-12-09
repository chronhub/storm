<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\QuerySubscriber;
use Chronhub\Storm\Projector\Scheme\QueryProjectorScope;
use Chronhub\Storm\Projector\Scheme\Sprint;

beforeEach(function () {
    $this->subscription = $this->createMock(QuerySubscriber::class);
    $this->sprint = new Sprint();
});

it('can stop subscription', function () {
    expect($this->sprint->inProgress())->toBeFalse();

    $this->sprint->continue();

    expect($this->sprint->inProgress())->toBeTrue();

    $this->subscription->expects($this->once())->method('sprint')->willReturn($this->sprint);

    $scope = new QueryProjectorScope($this->subscription);

    $scope->stop();
});

it('can get current stream name', function () {
    $this->subscription->expects($this->once())->method('currentStreamName')->willReturn('customer');

    $scope = new QueryProjectorScope($this->subscription);

    expect($scope->streamName())->toBe('customer');
});

it('can get clock', function () {
    $clock = $this->createMock(SystemClock::class);

    $this->subscription->expects($this->once())->method('clock')->willReturn($clock);

    $scope = new QueryProjectorScope($this->subscription);

    expect($scope->clock())->toBe($clock);
});
