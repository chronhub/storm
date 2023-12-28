<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\CheckpointRecognition;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionMangagement;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Projector\Support\EventCounter;
use Chronhub\Storm\Projector\Workflow\Activity\PersistOrUpdate;

beforeEach(function () {
    $this->subscription = $this->createMock(PersistentSubscriptionMangagement::class);
    $this->streamManager = $this->createMock(CheckpointRecognition::class);
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
