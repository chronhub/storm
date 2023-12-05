<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Projector\Activity\MonitorRemoteStatus;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\Sprint;

beforeEach(function (): void {
    $this->subscription = $this->createMock(PersistentSubscriptionInterface::class);
    $this->sprint = new Sprint();
});

dataset('is first cycle', [true, false]);

function getInstance(): object
{
    return new class
    {
        use MonitorRemoteStatus;

        public function getFirstCycle(): bool
        {
            return $this->isFirstCycle;
        }

        public function disableFlag(): void
        {
            $this->isFirstCycle = false;
        }

        public function shouldStop(PersistentSubscriptionInterface $subscription): bool
        {
            return $this->shouldStopOnDiscoveringStatus($subscription);
        }

        public function refresh(PersistentSubscriptionInterface $subscription): void
        {
            $this->refreshStatus($subscription);
        }

        public function onDiscovering(PersistentSubscriptionInterface $subscription): bool
        {
            return $this->discovering($subscription);
        }
    };
}

it('assert first cycle return true on new instance', function (): void {
    $instance = getInstance();

    $this->assertTrue($instance->getFirstCycle());
});

it('assert first cycle return false when disable flag', function (): void {
    $instance = getInstance();

    $this->assertTrue($instance->getFirstCycle());

    $instance->disableFlag();

    $this->assertFalse($instance->getFirstCycle());
});

it('discover stopping status', function (bool $firstExecution): void {
    $instance = getInstance();

    expect($instance->getFirstCycle())->toBeTrue();

    $this->subscription->expects($this->once())->method('disclose')->willReturn(ProjectionStatus::STOPPING);

    if (! $firstExecution) {
        $instance->disableFlag();
    } else {
        $this->subscription->expects($this->once())->method('synchronise');
    }

    $this->subscription->expects($this->once())->method('close');

    $shouldStop = $instance->onDiscovering($this->subscription);

    expect($shouldStop)->toBe($firstExecution);
})->with('is first cycle');

it('discover resetting status', function (bool $firstExecution, bool $inBackground): void {
    $instance = getInstance();

    expect($instance->getFirstCycle())->toBeTrue();

    $this->subscription->expects($this->once())->method('disclose')->willReturn(ProjectionStatus::RESETTING);
    $this->subscription->expects($this->once())->method('revise');
    $this->subscription->method('sprint')->willReturn($this->sprint);

    if (! $firstExecution) {
        $instance->disableFlag();
    }

    $this->subscription->sprint()->runInBackground($inBackground);

    if (! $firstExecution && $inBackground) {
        $this->subscription->expects($this->once())->method('restart');
    } else (
        $this->subscription->expects($this->never())->method('restart')
    );

    $shouldStop = $instance->onDiscovering($this->subscription);

    expect($shouldStop)->toBeFalse();
})
    ->with('is first cycle')
    ->with(['run in background' => [true], 'run once' => [false]]);

it('discover deleting status', function (bool $firstExecution, bool $shouldDiscardEvents): void {

    $instance = getInstance();

    expect($instance->getFirstCycle())->toBeTrue();

    $status = $shouldDiscardEvents ? ProjectionStatus::DELETING_WITH_EMITTED_EVENTS : ProjectionStatus::DELETING;
    $this->subscription->expects($this->once())->method('disclose')->willReturn($status);
    $this->subscription->expects($this->once())->method('discard')->with($shouldDiscardEvents);

    if (! $firstExecution) {
        $instance->disableFlag();
    }

    $shouldStop = $instance->onDiscovering($this->subscription);

    expect($shouldStop)->toBe($firstExecution);
})
    ->with('is first cycle')
    ->with(['with events' => [true], 'without events' => [false]]);

it('discover other status with no interaction', function (bool $firstExecution, ProjectionStatus $notHandled): void {
    $instance = getInstance();

    expect($instance->getFirstCycle())->toBeTrue();

    $this->subscription->expects($this->once())->method('disclose')->willReturn($notHandled);
    $this->subscription->expects($this->never())->method('discard');
    $this->subscription->expects($this->never())->method('close');
    $this->subscription->expects($this->never())->method('revise');
    $this->subscription->expects($this->never())->method('synchronise');

    if (! $firstExecution) {
        $instance->disableFlag();
    }

    $shouldStop = $instance->onDiscovering($this->subscription);

    expect($shouldStop)->toBeFalse();
})
    ->with('is first cycle')
    ->with([[ProjectionStatus::RUNNING], [ProjectionStatus::IDLE]]);

it('call should stop on discovering status and set first execution to false', function (): void {
    $instance = getInstance();

    expect($instance->getFirstCycle())->toBeTrue();

    $this->subscription->expects($this->once())->method('disclose')->willReturn(ProjectionStatus::RUNNING);

    $instance->shouldStop($this->subscription);

    expect($instance->getFirstCycle())->toBeFalse();
});

it('call discover status and set first execution to false', function (): void {
    $instance = getInstance();

    expect($instance->getFirstCycle())->toBeTrue();

    $this->subscription->expects($this->once())->method('disclose')->willReturn(ProjectionStatus::RUNNING);

    $instance->refresh($this->subscription);

    expect($instance->getFirstCycle())->toBeFalse();
});
