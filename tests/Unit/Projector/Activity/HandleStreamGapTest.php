<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Projector\Activity\HandleStreamGap;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Closure;

beforeEach(function () {
    $this->subscription = $this->createMock(PersistentSubscriptionInterface::class);
    $this->streamManager = $this->createMock(StreamManagerInterface::class);
    $this->eventCounter = new EventCounter(10);

    $this->activity = new HandleStreamGap();
    $this->next = fn (): Closure => fn (): int => 42;
});

it('sleep and store stream positions and user state when gap is detected', function () {
    $this->streamManager->expects($this->once())->method('hasGap')->willReturn(true);
    $this->streamManager->expects($this->once())->method('sleep');

    // fake an event handled
    $this->eventCounter->increment();
    $this->subscription->expects($this->once())->method('eventCounter')->willReturn($this->eventCounter);
    $this->subscription->expects($this->exactly(2))->method('streamManager')->willReturn($this->streamManager);
    $this->subscription->expects($this->once())->method('store');

    $returned = ($this->activity)($this->subscription, $this->next);

    expect($returned())->toBe(42);
});

it('sleep but not store when gap is detected and no event handled', function () {
    $this->streamManager->expects($this->once())->method('hasGap')->willReturn(true);
    $this->streamManager->expects($this->once())->method('sleep');

    expect($this->eventCounter->isReset())->toBeTrue();
    $this->subscription->expects($this->once())->method('eventCounter')->willReturn($this->eventCounter);
    $this->subscription->expects($this->exactly(2))->method('streamManager')->willReturn($this->streamManager);
    $this->subscription->expects($this->never())->method('store');

    $returned = ($this->activity)($this->subscription, $this->next);

    expect($returned())->toBe(42);
});
