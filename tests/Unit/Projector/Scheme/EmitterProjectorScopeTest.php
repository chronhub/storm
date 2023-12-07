<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Projector\Scheme\EmitterProjectorScope;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;

beforeEach(function () {
    $this->subscription = $this->createMock(EmitterSubscriptionInterface::class);
});

it('can emit event', function () {
    $event = SomeEvent::fromContent(['name' => 'steph']);

    $this->subscription->expects($this->once())->method('emit')->with($event);

    $scope = new EmitterProjectorScope($this->subscription);

    $scope->emit($event);
});

it('can link event to stream', function () {
    $event = SomeEvent::fromContent(['name' => 'steph']);

    $this->subscription->expects($this->once())->method('linkTo')->with('customer', $event);

    $scope = new EmitterProjectorScope($this->subscription);

    $scope->linkTo('customer', $event);
});

it('can stop subscription', function () {
    $this->subscription->expects($this->once())->method('close');

    $scope = new EmitterProjectorScope($this->subscription);

    $scope->stop();
});

it('can get current stream name', function () {
    $this->subscription->expects($this->once())->method('currentStreamName')->willReturn('customer');

    $scope = new EmitterProjectorScope($this->subscription);

    expect($scope->streamName())->toBe('customer');
});

it('can get clock', function () {
    $clock = $this->createMock(SystemClock::class);

    $this->subscription->expects($this->once())->method('clock')->willReturn($clock);

    $scope = new EmitterProjectorScope($this->subscription);

    expect($scope->clock())->toBe($clock);
});
