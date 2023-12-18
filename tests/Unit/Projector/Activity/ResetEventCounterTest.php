<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Projector\Support\EventCounter;
use Chronhub\Storm\Projector\Workflow\Activity\ResetEventCounter;

use function count;

it('reset event counter even if is already reset', function () {
    $eventCounter = new EventCounter(5);
    $subscription = $this->createMock(PersistentSubscriber::class);
    $subscription->expects($this->once())->method('eventCounter')->willReturn($eventCounter);

    expect($eventCounter->isReset())->toBeTrue();

    $activity = new ResetEventCounter();
    $nextSub = fn () => fn () => 42;

    $next = $activity($subscription, $nextSub);

    expect($next())->toBe(42);
});

it('reset incremented event counter', function (int $count) {
    $eventCounter = new EventCounter(5);
    $subscription = $this->createMock(PersistentSubscriber::class);
    $subscription->expects($this->once())->method('eventCounter')->willReturn($eventCounter);

    $inc = $count;
    while ($inc !== 0) {
        $eventCounter->increment();
        $inc--;
    }

    expect(count($eventCounter))->toBe($count);

    $activity = new ResetEventCounter();
    $nextSub = fn () => fn () => 42;

    $next = $activity($subscription, $nextSub);

    expect($next())->toBe(42);
})->with(['increment counter' => [1, 3, 5, 10]]);
