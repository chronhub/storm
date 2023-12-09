<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;
use Chronhub\Storm\Projector\Scheme\ReadModelProjectorScope;

beforeEach(function () {
    $this->subscription = $this->createMock(ReadModelSubscriber::class);
});

it('can stop subscription', function () {
    $this->subscription->expects($this->once())->method('close');

    $scope = new ReadModelProjectorScope($this->subscription);

    $scope->stop();
});

it('can get current stream name', function () {
    $this->subscription->expects($this->once())->method('currentStreamName')->willReturn('customer');

    $scope = new ReadModelProjectorScope($this->subscription);

    expect($scope->streamName())->toBe('customer');
});

it('can get clock', function () {
    $clock = $this->createMock(SystemClock::class);

    $this->subscription->expects($this->once())->method('clock')->willReturn($clock);

    $scope = new ReadModelProjectorScope($this->subscription);

    expect($scope->clock())->toBe($clock);
});

it('can get read model', function () {
    $readModel = $this->createMock(ReadModel::class);

    $this->subscription->expects($this->once())->method('readModel')->willReturn($readModel);

    $scope = new ReadModelProjectorScope($this->subscription);

    expect($scope->readModel())->toBe($readModel);
});
