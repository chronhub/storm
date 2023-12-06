<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Projector\Activity\PersistOrUpdate;
use Chronhub\Storm\Projector\Scheme\EventCounter;

beforeEach(function () {
    $this->subscription = $this->createMock(PersistentSubscriptionInterface::class);
    $this->streamManager = $this->createMock(StreamManagerInterface::class);
    $this->option = $this->createMock(ProjectionOption::class);
    $this->eventCounter = new EventCounter(2);

    $this->subscription->expects($this->once())->method('streamManager')->willReturn($this->streamManager);
    $this->next = fn () => fn () => 42;
    $this->activity = new PersistOrUpdate();
});

it('return next when gap is detected', function () {
    $this->streamManager->expects($this->once())->method('hasGap')->willReturn(true);
    $this->subscription->expects($this->never())->method('eventCounter');

    $next = ($this->activity)($this->subscription, $this->next);

    expect($next())->toBe(42);
});

it('sleep and update lock when gap is detected and no event handled', function () {
    $this->streamManager->expects($this->once())->method('hasGap')->willReturn(false);

    expect($this->eventCounter->isReset())->toBeTrue();
    $this->subscription->expects($this->once())->method('eventCounter')->willReturn($this->eventCounter);

    $this->option->expects($this->once())->method('getSleep')->willReturn(1);
    $this->subscription->expects($this->once())->method('option')->willReturn($this->option);

    $this->subscription->expects($this->once())->method('update');
    $this->subscription->expects($this->never())->method('store');

    $next = ($this->activity)($this->subscription, $this->next);

    expect($next())->toBe(42);
});

it('store stream positions and user state when no gap detected and events handled', function () {
    $this->streamManager->expects($this->once())->method('hasGap')->willReturn(false);

    $this->eventCounter->increment();
    expect($this->eventCounter->isReset())->toBeFalse();
    $this->subscription->expects($this->once())->method('eventCounter')->willReturn($this->eventCounter);

    $this->subscription->expects($this->never())->method('option');
    $this->subscription->expects($this->never())->method('update');

    $this->subscription->expects($this->once())->method('store');

    $next = ($this->activity)($this->subscription, $this->next);

    expect($next())->toBe(42);
});
